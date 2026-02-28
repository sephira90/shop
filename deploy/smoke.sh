#!/usr/bin/env bash
set -euo pipefail

composer run ops:production-smoke-core

echo "Deployment smoke checks passed"
