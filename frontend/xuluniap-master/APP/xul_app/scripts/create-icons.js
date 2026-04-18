/**
 * 生成 TabBar 占位图标
 * 运行: node scripts/create-icons.js
 */

const fs = require('fs');
const path = require('path');

const outDir = path.join(__dirname, '..', 'src', 'static', 'tabbar');

// 简单的 48x48 PNG 占位符（灰色圆形）
// 实际项目需要设计精美的图标
const GRAY_CIRCLE = Buffer.from([
  0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A, // PNG header
  // ... 简化，实际需要完整 PNG
]);

// 由于生成 PNG 复杂，这里创建占位说明
const icons = [
  'wallet.png', 'wallet-active.png',
  'browser.png', 'browser-active.png',
  'ai.png', 'ai-active.png',
  'settings.png', 'settings-active.png',
  'transfer.png', 'transfer-active.png',
  'history.png', 'history-active.png',
];

// 创建 1x1 透明 PNG 作为占位
const emptyPng = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA' +
  'eklEQVRoge3OMQEAIAwAsfJ/aMhxErMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
  'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOB2ALJmAewJX3xfAAAAAElFTkSuQmCC',
  'base64'
);

icons.forEach(name => {
  const filePath = path.join(outDir, name);
  fs.writeFileSync(filePath, emptyPng);
  console.log(`Created: ${name}`);
});

console.log('\n⚠️  这些是占位图标，需要在设计工具中创建精美图标后替换。');
