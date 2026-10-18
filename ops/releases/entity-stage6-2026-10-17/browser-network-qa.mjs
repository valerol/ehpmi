import fs from 'node:fs';
import path from 'node:path';

const endpoint = process.argv[2] || 'http://127.0.0.1:9223';
const outputDirectory = process.argv[3] || '/private/tmp';
const targetUrl = process.argv[4] || 'https://dev.ehpmi.org/';

class CdpClient {
    constructor(socket) {
        this.socket = socket;
        this.nextId = 1;
        this.pending = new Map();
        this.listeners = new Map();

        socket.addEventListener('message', (event) => {
            const message = JSON.parse(event.data);

            if (message.id && this.pending.has(message.id)) {
                const pending = this.pending.get(message.id);
                this.pending.delete(message.id);
                if (message.error) {
                    pending.reject(new Error(message.error.message));
                } else {
                    pending.resolve(message.result || {});
                }
                return;
            }

            (this.listeners.get(message.method) || []).forEach((listener) => listener(message.params || {}));
        });
    }

    static async connect(webSocketUrl) {
        const socket = new WebSocket(webSocketUrl);
        await new Promise((resolve, reject) => {
            socket.addEventListener('open', resolve, { once: true });
            socket.addEventListener('error', reject, { once: true });
        });
        return new CdpClient(socket);
    }

    send(method, params = {}) {
        const id = this.nextId++;
        const promise = new Promise((resolve, reject) => this.pending.set(id, { resolve, reject }));
        this.socket.send(JSON.stringify({ id, method, params }));
        return promise;
    }

    on(method, listener) {
        const listeners = this.listeners.get(method) || [];
        listeners.push(listener);
        this.listeners.set(method, listeners);
    }

    waitFor(method, timeout = 15000) {
        return new Promise((resolve, reject) => {
            const timer = setTimeout(() => reject(new Error(`Timed out waiting for ${method}`)), timeout);
            const listener = (params) => {
                clearTimeout(timer);
                const listeners = this.listeners.get(method) || [];
                this.listeners.set(method, listeners.filter((candidate) => candidate !== listener));
                resolve(params);
            };
            this.on(method, listener);
        });
    }

    close() {
        this.socket.close();
    }
}

const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

function imageFamily(url) {
    try {
        const pathname = new URL(url).pathname;
        const filename = decodeURIComponent(pathname.substring(pathname.lastIndexOf('/') + 1));
        return filename.replace(/-\d+x\d+(?=\.[^.]+$)/, '');
    } catch {
        return '';
    }
}

async function evaluate(client, expression) {
    const response = await client.send('Runtime.evaluate', {
        expression,
        awaitPromise: true,
        returnByValue: true
    });
    if (response.exceptionDetails) {
        throw new Error(response.exceptionDetails.text || 'Runtime evaluation failed');
    }
    return response.result.value;
}

async function setViewport(client, width, height, mobile) {
    await client.send('Emulation.setDeviceMetricsOverride', {
        width,
        height,
        deviceScaleFactor: 1,
        mobile
    });
}

async function capture(client, filename) {
    const screenshot = await client.send('Page.captureScreenshot', {
        format: 'png',
        captureBeyondViewport: false
    });
    fs.writeFileSync(path.join(outputDirectory, filename), Buffer.from(screenshot.data, 'base64'));
}

const pageResponse = await fetch(`${endpoint}/json/new?${encodeURIComponent('about:blank')}`, { method: 'PUT' });
if (!pageResponse.ok) {
    throw new Error(`Could not create Chrome target: ${pageResponse.status}`);
}

const page = await pageResponse.json();
const client = await CdpClient.connect(page.webSocketDebuggerUrl);
const requests = [];
client.on('Network.requestWillBeSent', (event) => {
    requests.push({ type: event.type, url: event.request.url });
});

await Promise.all([
    client.send('Network.enable'),
    client.send('Page.enable'),
    client.send('Runtime.enable')
]);

await setViewport(client, 390, 844, true);
await client.send('Page.navigate', { url: `${targetUrl}?stage6-mobile-gate=${Date.now()}` });
const domReadyDeadline = Date.now() + 15000;
let readyState = 'loading';
while (Date.now() < domReadyDeadline) {
    readyState = await evaluate(client, 'document.readyState');
    if (readyState !== 'loading') {
        break;
    }
    await sleep(250);
}
if (readyState === 'loading') {
    throw new Error('Timed out waiting for DOM readiness');
}
await sleep(3000);

const mobileRequestBoundary = requests.length;
const mobile = await evaluate(client, `(() => {
    const hero = document.querySelector('[data-ehpmi-desktop-carousel]');
    const text = document.querySelector('.hero .text');
    const textStyle = text ? getComputedStyle(text) : null;
    const textRect = text ? text.getBoundingClientRect() : null;
    return {
        viewport: [innerWidth, innerHeight],
        heroFound: Boolean(hero),
        heroDisplay: hero ? getComputedStyle(hero).display : null,
        textVisible: Boolean(text && textStyle.display !== 'none' && textStyle.visibility !== 'hidden' && textRect.width > 0 && textRect.height > 0),
        instanceExists: Boolean(hero && bootstrap.Carousel.getInstance(hero)),
        sourceSrcsets: Array.from(document.querySelectorAll('[data-ehpmi-desktop-carousel] source')).map((source) => source.srcset),
        images: Array.from(document.querySelectorAll('[data-ehpmi-desktop-carousel] img')).map((image) => ({
            alt: image.alt,
            currentSrc: image.currentSrc,
            src: image.src,
            width: image.getAttribute('width'),
            height: image.getAttribute('height'),
            loading: image.loading,
            fetchpriority: image.getAttribute('fetchpriority') || ''
        }))
    };
})()`);

const heroFamilies = [...new Set(
    mobile.sourceSrcsets.flatMap((srcset) => srcset.match(/https?:\/\/[^,\s]+/g) || []).map(imageFamily).filter(Boolean)
)];
const mobileHeroRequests = requests.slice(0, mobileRequestBoundary).filter((request) => {
    if (request.type !== 'Image') {
        return false;
    }
    const family = imageFamily(request.url);
    return family && heroFamilies.includes(family);
});

await capture(client, 'mobile-gate-390.png');

await setViewport(client, 1440, 900, false);
await sleep(2000);

const desktopInitial = await evaluate(client, `(() => {
    const hero = document.querySelector('[data-ehpmi-desktop-carousel]');
    const instance = hero ? bootstrap.Carousel.getInstance(hero) : null;
    return {
        viewport: [innerWidth, innerHeight],
        heroDisplay: hero ? getComputedStyle(hero).display : null,
        instanceExists: Boolean(instance),
        intervalActive: Boolean(instance && instance._interval),
        activeIndex: hero ? Array.from(hero.querySelectorAll('.carousel-item')).findIndex((item) => item.classList.contains('active')) : -1,
        images: hero ? Array.from(hero.querySelectorAll('img')).map((image) => ({ currentSrc: image.currentSrc, complete: image.complete, naturalWidth: image.naturalWidth })) : []
    };
})()`);

const transition = await evaluate(client, `(async () => {
    const hero = document.querySelector('[data-ehpmi-desktop-carousel]');
    const instance = bootstrap.Carousel.getInstance(hero);
    const items = Array.from(hero.querySelectorAll('.carousel-item'));
    const before = items.findIndex((item) => item.classList.contains('active'));
    instance.pause();
    instance.next();
    await new Promise((resolve) => setTimeout(resolve, 900));
    const after = items.findIndex((item) => item.classList.contains('active'));
    instance.cycle();
    return { before, after, intervalActive: Boolean(instance._interval) };
})()`);

for (let index = 0; index < 3; index += 1) {
    await evaluate(client, `(() => {
        const hero = document.querySelector('[data-ehpmi-desktop-carousel]');
        bootstrap.Carousel.getInstance(hero).to(${index});
    })()`);
    await sleep(900);
}

const desktopFinal = await evaluate(client, `(() => {
    const hero = document.querySelector('[data-ehpmi-desktop-carousel]');
    const instance = bootstrap.Carousel.getInstance(hero);
    return {
        instanceExists: Boolean(instance),
        intervalActive: Boolean(instance && instance._interval),
        images: Array.from(hero.querySelectorAll('img')).map((image) => ({
            alt: image.alt,
            currentSrc: image.currentSrc,
            complete: image.complete,
            naturalWidth: image.naturalWidth
        }))
    };
})()`);

await capture(client, 'desktop-gate-1440.png');

await setViewport(client, 390, 844, true);
await sleep(1000);
const mobileAfterDesktop = await evaluate(client, `(() => {
    const hero = document.querySelector('[data-ehpmi-desktop-carousel]');
    const text = document.querySelector('.hero .text');
    return {
        viewport: [innerWidth, innerHeight],
        heroDisplay: getComputedStyle(hero).display,
        instanceExists: Boolean(bootstrap.Carousel.getInstance(hero)),
        textVisible: Boolean(text && getComputedStyle(text).display !== 'none' && text.getBoundingClientRect().height > 0)
    };
})()`);

const failures = [];
if (!mobile.heroFound || mobile.heroDisplay !== 'none' || !mobile.textVisible) failures.push('mobile visibility contract failed');
if (mobile.instanceExists) failures.push('Hero carousel was initialized on initial mobile load');
if (mobile.images.length !== 3 || mobile.images.some((image) => !image.src.startsWith('data:image/gif;base64,') || !image.currentSrc.startsWith('data:image/gif;base64,') || !image.width || !image.height)) failures.push('mobile placeholder/dimensions contract failed');
if (mobile.images[0]?.loading !== 'eager' || mobile.images[0]?.fetchpriority !== 'high' || mobile.images.slice(1).some((image) => image.loading !== 'lazy')) failures.push('loading priority contract failed');
if (mobileHeroRequests.length !== 0) failures.push('external Hero image requested on initial mobile load');
if (desktopInitial.heroDisplay === 'none' || !desktopInitial.instanceExists || !desktopInitial.intervalActive) failures.push('desktop Hero carousel initialization failed');
if (transition.before === transition.after || !transition.intervalActive) failures.push('desktop Hero carousel transition/cycle failed');
if (!desktopFinal.instanceExists || desktopFinal.images.length !== 3 || desktopFinal.images.some((image) => image.currentSrc.startsWith('data:') || !image.complete || image.naturalWidth <= 0)) failures.push('desktop Hero image loading failed');
if (mobileAfterDesktop.heroDisplay !== 'none' || mobileAfterDesktop.instanceExists || !mobileAfterDesktop.textVisible) failures.push('desktop-to-mobile disposal failed');

const result = {
    status: failures.length ? 'FAIL' : 'PASS',
    failures,
    targetUrl,
    mobile,
    mobileHeroRequests,
    desktopInitial,
    desktopTransition: transition,
    desktopFinal,
    mobileAfterDesktop,
    requestCount: requests.length
};

fs.writeFileSync(path.join(outputDirectory, 'browser-network-qa.json'), `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify(result, null, 2));
client.close();
process.exit(failures.length ? 1 : 0);
