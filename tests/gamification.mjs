import { spawnSync } from 'node:child_process';

for (const suite of ['quest', 'mastery', 'minigame', 'pvp', 'demo-fixture', 'teacher-export', 'classes-http', 'mastery-http', 'minigame-http', 'teacher-export-http', 'pvp-http', 'upgrade']) {
  const result = spawnSync(process.execPath, ['tests/run.mjs', suite], { stdio: 'inherit', env: process.env });
  if (result.error) throw result.error;
  if (result.status !== 0) process.exit(result.status ?? 1);
}
