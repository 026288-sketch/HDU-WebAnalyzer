from sentence_transformers import SentenceTransformer
import os

print("🤖 Downloading Sentence Transformers model...")
print("📦 Model: sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2")
print()

try:
    model = SentenceTransformer('sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2')
    print("✅ Model downloaded successfully!")
    
    # Получаем путь к кэшу HuggingFace
    cache_path = os.path.expanduser("~/.cache/huggingface/hub")
    print(f"📍 Location: {cache_path}")
except Exception as e:
    print(f"❌ Error: {e}")
    exit(1)