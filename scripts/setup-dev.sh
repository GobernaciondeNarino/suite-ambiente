#!/usr/bin/env bash
# Prepara el entorno de desarrollo de Suite Ambiente Nariño.
# Idempotente: se puede ejecutar varias veces. Lo usa el hook SessionStart
# de Claude Code en sesiones remotas (CLAUDE_CODE_REMOTE=true).
set -euo pipefail

cd "$(dirname "$0")/.."

# 1. Repomix (devDependency npm).
if [ ! -d node_modules/repomix ]; then
	npm ci --no-audit --no-fund >/dev/null 2>&1 || npm install --no-audit --no-fund >/dev/null 2>&1 || true
fi

# 2. graphify (CLI Python) — la skill y los hooks ya están versionados en .claude/.
if ! command -v graphify >/dev/null 2>&1; then
	if command -v uv >/dev/null 2>&1; then
		uv tool install graphifyy >/dev/null 2>&1 || true
	elif command -v pipx >/dev/null 2>&1; then
		pipx install graphifyy >/dev/null 2>&1 || true
	else
		python3 -m pip install --quiet --user graphifyy >/dev/null 2>&1 || true
	fi
fi

echo "Entorno listo: repomix $(npx --no-install repomix --version 2>/dev/null || echo 'no instalado'), graphify $(graphify --version 2>/dev/null | awk '{print $2}' || echo 'no instalado')."
