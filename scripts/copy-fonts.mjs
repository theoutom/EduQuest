import { copyFile, mkdir } from 'node:fs/promises';

const target = new URL('../public/assets/fonts/', import.meta.url);
await mkdir(target, { recursive: true });
for (const family of ['inter', 'plus-jakarta-sans']) {
  const source = new URL('../node_modules/@fontsource-variable/' + family + '/', import.meta.url);
  await copyFile(new URL('files/' + family + '-latin-wght-normal.woff2', source), new URL(family + '.woff2', target));
  await copyFile(new URL('LICENSE', source), new URL(family + '-LICENSE.txt', target));
}
