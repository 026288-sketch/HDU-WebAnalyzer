import express from 'express';
import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import cors from 'cors';
import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';

// Enable stealth plugin to avoid bot detection
puppeteer.use(StealthPlugin());

// For correct __dirname in ESM
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load environment variables from .env
dotenv.config({ path: path.join(__dirname, '..', '.env') });

const app = express();

// Use PORT and HOST from environment variables or defaults
const PORT = process.env.PUPPETEER_PORT || 3000;
const HOST = process.env.PUPPETEER_HOST || '127.0.0.1';

// Enable CORS for cross-origin requests
app.use(cors());

/**
 * Health check endpoint
 *
 * @route GET /health
 * @returns {Object} JSON status message
 */
app.get('/health', (req, res) => {
  res.json({
    status: "ok",
    message: "Service is running"
  });
});

/**
 * Scraping endpoint
 *
 * Launches Puppeteer to fetch the full HTML content of a page.
 *
 * @route GET /scrape
 * @query {string} source - The URL to scrape
 * @returns {string} HTML content of the page
 */
app.get('/scrape', async (req, res) => {
  const source = req.query.source;

  if (!source) {
    return res.status(400).send('Missing ?source parameter');
  }

  let browser;
  try {
    // Launch Puppeteer browser
    browser = await puppeteer.launch({
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    //domcontentloaded
    const page = await browser.newPage();
    await page.goto(source, {
      waitUntil: 'networkidle0',
      timeout: 30000
    });

    const html = await page.content();
    await browser.close();

    res.send(html);
  } catch (err) {
    console.error('Scraping error:', err);

    if (browser) {
      await browser.close().catch(console.error);
    }

    res.status(500).send(`Error scraping page: ${err.message}`);
  }
});

/**
 * Start Puppeteer server
 */
app.listen(PORT, HOST, () => {
  console.log(`Puppeteer server running at http://${HOST}:${PORT}`);
});

/**
 * Graceful shutdown handler
 */
process.on('SIGINT', () => {
  console.log('Shutting down Puppeteer server...');
  process.exit(0);
});
