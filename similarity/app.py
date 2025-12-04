from fastapi import FastAPI, HTTPException, Request
from pydantic import BaseModel, Field
import chromadb
from chromadb.utils import embedding_functions
import logging
import logging.handlers
from pathlib import Path
from bs4 import BeautifulSoup
import re
import uuid
import os

logging.disable(logging.CRITICAL)

logger = logging.getLogger(__name__)

app = FastAPI()

# Configuration
THRESHOLD = float(os.getenv("SIM_THRESHOLD", "0.92"))
THRESHOLD_SUMMARY = float(os.getenv("SIM_THRESHOLD_SUMMARY", "0.95"))
CHUNK_SIZE = int(os.getenv("SIM_CHUNK_SIZE", "500"))
MIN_CHUNK_RATIO = float(os.getenv("SIM_MIN_CHUNK_RATIO", "0.6"))
MIN_CHUNK_SIZE = int(os.getenv("SIM_MIN_CHUNK_SIZE", "0"))
USE_HYBRID = os.getenv("SIM_USE_HYBRID", "true").lower() == "true"

# Embedding models
EMBEDDING_FUNC = embedding_functions.SentenceTransformerEmbeddingFunction(
    model_name="sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2",
    device="cpu"
)

# ChromaDB initialization
client = chromadb.PersistentClient(
    path="./chroma_db",
    settings=chromadb.Settings(anonymized_telemetry=False)
)

collection = client.get_or_create_collection(
    name="articles",
    embedding_function=EMBEDDING_FUNC,
    metadata={"hnsw:space": "cosine"}
)

# Pydantic models
class Item(BaseModel):
    content: str
    summary: str = ""

class DeleteBatch(BaseModel):
    parent_ids: list[str] = Field(default_factory=list)
    chunk_ids: list[str] = Field(default_factory=list)
    ids: list[str] = Field(default_factory=list)

class GetParentIdsRequest(BaseModel):
    chunk_ids: list[str]


# Clean text function
def clean_text(text: str) -> str:
    text = BeautifulSoup(text, "html.parser").get_text()
    text = re.sub(r"\s+", " ", text).strip().lower()
    return text

def chunk_text(text: str, size: int = CHUNK_SIZE):
    """Split text into chunks and avoid short tail"""
    if not text:
        return []

    chunks = [text[i:i+size] for i in range(0, len(text), size)]

    if len(chunks) >= 2:
        min_size = MIN_CHUNK_SIZE if MIN_CHUNK_SIZE > 0 else int(size * MIN_CHUNK_RATIO)
        if len(chunks[-1]) < max(1, min_size):
            chunks[-2] = chunks[-2] + chunks[-1]
            chunks.pop()

    return chunks


@app.post("/check")
async def check_duplicate(item: Item):
    """Check for duplicates and save to database"""
    try:
        clean_content = clean_text(item.content)
        if not clean_content.strip():
            raise HTTPException(status_code=400, detail="Text cannot be empty")

        # Hybrid approach: check summary first
        if USE_HYBRID and item.summary and len(item.summary.strip()) > 50:
            clean_summary = clean_text(item.summary)
            if clean_summary:
                summary_results = collection.query(
                    query_texts=[clean_summary],
                    n_results=3,
                    include=["documents", "distances", "metadatas"]
                )

                if summary_results["distances"] and len(summary_results["distances"][0]) > 0:
                    best_summary_sim = 1 - summary_results["distances"][0][0]

                    if best_summary_sim >= THRESHOLD_SUMMARY:
                        full_results = collection.query(
                            query_texts=[clean_content[:2000]],
                            n_results=1,
                            include=["documents", "distances", "metadatas"]
                        )

                        if full_results["distances"] and len(full_results["distances"][0]) > 0:
                            full_sim = 1 - full_results["distances"][0][0]
                            if full_sim >= THRESHOLD:
                                matched_text = full_results["documents"][0][0]
                                return {
                                    "duplicate": True,
                                    "similarity": round(full_sim, 4),
                                    "matched_preview": matched_text[:200] + "..." if len(matched_text) > 200 else matched_text,
                                    "reason": "Full text match (after summary check)",
                                    "chroma_id": full_results["ids"][0][0],
                                    "threshold": THRESHOLD,
                                    "method": "hybrid_summary_full"
                                }

        chunks = chunk_text(clean_content)
        if not chunks:
            raise HTTPException(status_code=400, detail="Text resulted in 0 chunks")

        results = collection.query(
            query_texts=chunks,
            n_results=1,
            include=["documents", "distances", "metadatas"]
        )

        best_similarity = 0
        best_match_preview = ""

        for chunk_idx, distances in enumerate(results["distances"]):
            if not distances:
                continue

            distance = distances[0]
            similarity = 1 - distance
            matched_text = results["documents"][chunk_idx][0]

            if similarity > best_similarity:
                best_similarity = similarity
                best_match_preview = matched_text[:200] + "..." if len(matched_text) > 200 else matched_text

            if similarity >= THRESHOLD:
                matched_preview = matched_text[:200] + "..." if len(matched_text) > 200 else matched_text
                return {
                    "duplicate": True,
                    "similarity": round(similarity, 4),
                    "matched_preview": matched_preview,
                    "reason": f"Chunk {chunk_idx} is a duplicate.",
                    "chroma_id": results["ids"][chunk_idx][0],
                    "threshold": THRESHOLD,
                    "method": "chunks"
                }

        # No duplicates found, save to database
        parent_id = str(uuid.uuid4())
        chunk_ids = [f"{parent_id}_{i}" for i in range(len(chunks))]
        metadatas = [{"source": "article", "parent_id": parent_id, "chunk_index": i} for i in range(len(chunks))]

        collection.add(
            documents=chunks,
            ids=chunk_ids,
            metadatas=metadatas
        )

        return {
            "duplicate": False,
            "similarity": round(best_similarity, 4),
            "matched_preview": best_match_preview,
            "parent_id": parent_id,
            "chunk_ids": chunk_ids,
            "threshold": THRESHOLD,
            "method": "chunks"
        }

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error in check_duplicate: {str(e)}")
        raise HTTPException(status_code=500, detail="Internal server error")


@app.post("/check_only")
async def check_only(item: Item):
    """Check for duplicates WITHOUT saving to database"""
    try:
        clean_content = clean_text(item.content)
        if not clean_content.strip():
            raise HTTPException(status_code=400, detail="Text cannot be empty")

        # Hybrid approach: check summary first
        if USE_HYBRID and item.summary and len(item.summary.strip()) > 50:
            clean_summary = clean_text(item.summary)
            if clean_summary:
                summary_results = collection.query(
                    query_texts=[clean_summary],
                    n_results=3,
                    include=["documents", "distances", "metadatas"]
                )

                if summary_results["distances"] and len(summary_results["distances"][0]) > 0:
                    best_summary_sim = 1 - summary_results["distances"][0][0]

                    if best_summary_sim >= THRESHOLD_SUMMARY:
                        full_results = collection.query(
                            query_texts=[clean_content[:2000]],
                            n_results=1,
                            include=["documents", "distances", "metadatas"]
                        )

                        if full_results["distances"] and len(full_results["distances"][0]) > 0:
                            full_sim = 1 - full_results["distances"][0][0]
                            if full_sim >= THRESHOLD:
                                matched_text = full_results["documents"][0][0]
                                return {
                                    "duplicate": True,
                                    "similarity": round(full_sim, 4),
                                    "matched_preview": matched_text[:200] + "..." if len(matched_text) > 200 else matched_text,
                                    "reason": "Full text match (after summary check)",
                                    "chroma_id": full_results["ids"][0][0],
                                    "threshold": THRESHOLD,
                                    "method": "hybrid_summary_full"
                                }

        chunks = chunk_text(clean_content)
        if not chunks:
            raise HTTPException(status_code=400, detail="Text resulted in 0 chunks")

        results = collection.query(
            query_texts=chunks,
            n_results=1,
            include=["documents", "distances", "metadatas"]
        )

        best_similarity = 0
        best_match_preview = ""

        for chunk_idx, distances in enumerate(results["distances"]):
            if not distances:
                continue

            distance = distances[0]
            similarity = 1 - distance
            matched_text = results["documents"][chunk_idx][0]

            if similarity > best_similarity:
                best_similarity = similarity
                best_match_preview = matched_text[:200] + "..." if len(matched_text) > 200 else matched_text

            if similarity >= THRESHOLD:
                matched_preview = matched_text[:200] + "..." if len(matched_text) > 200 else matched_text
                return {
                    "duplicate": True,
                    "similarity": round(similarity, 4),
                    "matched_preview": matched_preview,
                    "reason": f"Chunk {chunk_idx} is a duplicate.",
                    "chroma_id": results["ids"][chunk_idx][0],
                    "threshold": THRESHOLD,
                    "method": "chunks"
                }

        return {
            "duplicate": False,
            "similarity": round(best_similarity, 4),
            "matched_preview": best_match_preview,
            "threshold": THRESHOLD,
            "method": "chunks"
        }

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error in check_only: {str(e)}")
        raise HTTPException(status_code=500, detail="Internal server error")


@app.post("/delete_batch")
async def delete_batch(item: DeleteBatch):
    """Delete documents by chunk IDs or parent IDs"""
    try:
        if not item.ids and not item.chunk_ids and not item.parent_ids:
            raise HTTPException(status_code=400, detail="No valid IDs provided")

        deleted_ids = []

        if item.ids:
            valid_ids = [cid for cid in item.ids if cid]
            if valid_ids:
                collection.delete(ids=valid_ids)
                collection.delete(where={"parent_id": {"$in": valid_ids}})
                deleted_ids.extend(valid_ids)

        if item.chunk_ids:
            valid_chunk_ids = [cid for cid in item.chunk_ids if cid]
            if valid_chunk_ids:
                collection.delete(ids=valid_chunk_ids)
                deleted_ids.extend(valid_chunk_ids)

        if item.parent_ids:
            valid_parent_ids = [pid for pid in item.parent_ids if pid]
            if valid_parent_ids:
                collection.delete(where={"parent_id": {"$in": valid_parent_ids}})
                deleted_ids.extend(valid_parent_ids)

        return {"status": "ok", "deleted_items": deleted_ids}

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error deleting documents: {str(e)}")
        raise HTTPException(status_code=500, detail="Internal server error")


@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {"status": "ok", "message": "Service is running"}


@app.get("/debug")
async def debug_chroma():
    """Debug endpoint to retrieve database contents"""
    try:
        data = collection.get(include=["documents", "metadatas"])
        count = collection.count()

        if not data["documents"]:
            return {"count": 0, "documents": []}

        documents_with_ids = [
            {
                "id": data["ids"][i],
                "document_preview": data["documents"][i][:100] + "...",
                "document_length": len(data["documents"][i]),
                "metadata": data["metadatas"][i] if data["metadatas"] and i < len(data["metadatas"]) else None
            }
            for i in range(len(data["documents"]))
        ]
        return {"count": count, "documents": documents_with_ids}

    except Exception as e:
        logger.error(f"Error in debug_chroma: {str(e)}")
        raise HTTPException(status_code=500, detail="Internal server error")


@app.post("/get_parent_ids")
async def get_parent_ids(request: GetParentIdsRequest):
    """Get parent_id for each chunk_id. Returns dict: {chunk_id: parent_id}"""
    try:
        if not request.chunk_ids:
            return {}

        data = collection.get(ids=request.chunk_ids, include=["metadatas"])
        result = {}
        found_ids = set(data.get("ids", []))

        for i, chunk_id in enumerate(data.get("ids", [])):
            if data.get("metadatas") and i < len(data["metadatas"]):
                metadata = data["metadatas"][i]
                if metadata and "parent_id" in metadata:
                    result[chunk_id] = metadata["parent_id"]
                else:
                    if "_" in chunk_id:
                        parts = chunk_id.rsplit("_", 1)
                        if len(parts) == 2 and parts[1].isdigit():
                            result[chunk_id] = parts[0]
                        else:
                            result[chunk_id] = chunk_id
                    else:
                        result[chunk_id] = chunk_id
            else:
                result[chunk_id] = chunk_id

        not_found = set(request.chunk_ids) - found_ids
        if not_found:
            logger.error(f"Some chunk_ids were not found: {not_found}")

        return result

    except Exception as e:
        logger.error(f"Error in get_parent_ids: {str(e)}")
        raise HTTPException(status_code=500, detail="Internal server error")


@app.get("/config")
async def get_config(request: Request):
    """Get current configuration"""
    try:
        return {
            "threshold": THRESHOLD,
            "threshold_summary": THRESHOLD_SUMMARY,
            "chunk_size": CHUNK_SIZE,
            "use_hybrid": USE_HYBRID
        }
    except Exception as e:
        logger.error(f"Error in get_config: {str(e)}")
        raise HTTPException(status_code=500, detail="Internal server error")
