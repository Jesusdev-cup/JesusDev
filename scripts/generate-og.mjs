// Generates public/og.png (1200x630 social card) — run: node scripts/generate-og.mjs
import sharp from 'sharp';

const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <rect width="1200" height="630" fill="#0a0e14"/>
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0d1219"/>
      <stop offset="1" stop-color="#0a0e14"/>
    </linearGradient>
    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
      <path d="M40 0H0v40" fill="none" stroke="#1f2a37" stroke-width="1" opacity="0.5"/>
    </pattern>
  </defs>
  <rect width="1200" height="630" fill="url(#g)"/>
  <rect width="1200" height="630" fill="url(#grid)"/>
  <rect x="60" y="60" width="1080" height="510" rx="18" fill="#11181f" stroke="#1f2a37" stroke-width="2"/>
  <circle cx="105" cy="105" r="9" fill="#ff5f57"/>
  <circle cx="135" cy="105" r="9" fill="#febc2e"/>
  <circle cx="165" cy="105" r="9" fill="#28c840"/>
  <text x="100" y="200" font-family="Consolas, monospace" font-size="34" fill="#22d3ee">$ whoami</text>
  <text x="100" y="290" font-family="Consolas, monospace" font-size="72" font-weight="bold" fill="#e6edf3">Jesús Gabriel Cupello</text>
  <text x="100" y="360" font-family="Consolas, monospace" font-size="34" fill="#8b98a9">&gt; Software Developer &amp; Systems Analyst</text>
  <text x="100" y="460" font-family="Consolas, monospace" font-size="28" fill="#3fb950">PHP · JavaScript · React · WordPress · n8n</text>
  <text x="100" y="520" font-family="Consolas, monospace" font-size="24" fill="#8b98a9">jesusdev-cup.github.io/JesusDev</text>
</svg>`;

await sharp(Buffer.from(svg)).png().toFile('public/og.png');
console.log('og.png generated');
