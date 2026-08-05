import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../../public/assets/js/password-visibility.js', import.meta.url), 'utf8');
assert.match(source, /target\.type = 'password'/);
assert.match(source, /control\.checked \? 'text' : 'password'/);
assert.doesNotMatch(source, /value|console\./);
