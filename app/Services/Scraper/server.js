import express from 'express';
import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
// Импортируем напрямую, раз проверка показала, что это работает
import AdblockerPlugin from 'puppeteer-extra-plugin-adblocker';
import cors from 'cors';

// -----------------------------
// Инициализация плагинов
// -----------------------------

// 1. Stealth Plugin: Скрывает автоматизацию
puppeteer.use(StealthPlugin());

// 2. Adblocker Plugin: Блокирует рекламу
// Подключаем с рекомендованной опцией блокировки трекеров
puppeteer.use(AdblockerPlugin({ blockTrackers: true }));

const app = express();
const PORT = 3000;
const HOST = '0.0.0.0';

const chromiumPath =
  process.env.PUPPETEER_EXECUTABLE_PATH || puppeteer.executablePath();

app.use(cors());

app.get('/health', (req, res) => {
  res.json({ status: 'ok', message: 'Puppeteer service running with Adblocker' });
});

app.get('/scrape', async (req, res) => {
  const source = req.query.source;

  if (!source) {
    return res.status(400).send('Missing ?source=URL');
  }

  let browser;

  try {
    browser = await puppeteer.launch({
      headless: 'new',
      executablePath: chromiumPath,
      args: [
        '--no-sandbox',
        '--disable-gpu',
        '--disable-dev-shm-usage',
        '--disable-setuid-sandbox',
        '--disable-infobars',
        '--window-size=1920,1080',
        '--disable-software-rasterizer',
        '--mute-audio',
      ],
    });

    const page = await browser.newPage();

    await page.setViewport({ width: 1920, height: 1080 });
    page.setDefaultNavigationTimeout(30000);

    // Adblocker работает автоматически для всех новых страниц
    // благодаря puppeteer.use() выше.

    await page.goto(source, {
      waitUntil: 'networkidle0',
      timeout: 30000,
    });

    const html = await page.content();
    res.send(html);

  } catch (err) {
    console.error('[PUPPETEER ERROR]:', err);
    res.status(500).send(`Error: ${err.message}`);
  } finally {
    if (browser) {
      await browser.close().catch(() => {});
    }
  }
});

app.listen(PORT, HOST, () => {
  console.log(`🚀 Server ready at http://${HOST}:${PORT}`);
});

process.on('SIGINT', () => {
  process.exit(0);
});