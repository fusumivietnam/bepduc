#!/usr/bin/env node
'use strict';
// Read-only contract: theme.json owns tokens; the registry catalogs live CSS selectors.
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const fail = msg => { throw new Error('Design registry: ' + msg); };
const registry = JSON.parse(read('design-system/registry.json'));
const theme = JSON.parse(read(registry.tokensSource));
if (registry.schemaVersion !== 1 || !/^https:\/\/www\.figma\.com\/design\//.test(registry.designMaster)) fail('invalid source');
const groups = {
  color: theme.settings.color.palette.map(v => v.slug),
  spacing: theme.settings.spacing.spacingSizes.map(v => v.slug),
  fontSize: theme.settings.typography.fontSizes.map(v => v.slug),
};
for (const [kind, names] of Object.entries(registry.requiredTokens)) {
  if (!groups[kind]) fail('unknown token group ' + kind);
  for (const name of names) if (!groups[kind].includes(name)) fail(kind + '/' + name + ' not in theme.json');
}
const ids = new Set();
for (const comp of registry.components) {
  if (!comp.id || ids.has(comp.id)) fail('missing/duplicate component ID ' + comp.id);
  ids.add(comp.id);
  if (!comp.selector.startsWith('.') || !Array.isArray(comp.states) || !comp.states.length) fail('invalid component ' + comp.id);
  const css = read(comp.stylesheet);
  if (!css.includes(comp.selector) && !comp.selector.startsWith('.wp-block-')) fail('selector '+comp.selector+' absent from '+comp.stylesheet);
}
console.log('Design registry validated:', ids.size, 'components;', Object.values(registry.requiredTokens).reduce((s,a)=>s+a.length,0), 'required tokens.');
