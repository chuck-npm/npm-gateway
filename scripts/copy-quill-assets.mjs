import { copyFile, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const repositoryRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const sourceRoot = resolve(repositoryRoot, 'node_modules', 'quill');
const destinationRoot = resolve(repositoryRoot, 'public', 'assets', 'vendor', 'quill', '2.0.3');
const assets = [
  ['dist/quill.js', 'quill.js'],
  ['dist/quill.snow.css', 'quill.snow.css'],
  ['LICENSE', 'LICENSE'],
];

try {
  await mkdir(destinationRoot, { recursive: true });
  for (const [source, destination] of assets) {
    await copyFile(resolve(sourceRoot, source), resolve(destinationRoot, destination));
  }
  process.stdout.write('Copied Quill 2.0.3 vendor assets.\n');
} catch (error) {
  process.stderr.write(`Unable to copy required Quill vendor asset: ${error.code ?? 'unknown error'}\n`);
  process.exitCode = 1;
}
