FROM node:18-alpine

# Install Chromium and dependencies for Puppeteer
RUN apk add --no-cache \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont

# Tell Puppeteer to skip installing Chrome. We'll be using the installed package.
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

# Set working directory
WORKDIR /app

# Copy package.json
COPY app/Services/Scraper/package*.json ./

# Install dependencies
RUN npm install

# Copy server code
COPY app/Services/Scraper/ ./

# Expose port
EXPOSE 3000

# Start the server
CMD ["node", "server.js"]