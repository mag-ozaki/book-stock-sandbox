#!/bin/bash
# PreToolUse フック: main / develop への直接 push をブロックする

INPUT=$(cat)
COMMAND=$(echo "$INPUT" | jq -r '.tool_input.command // empty')

if echo "$COMMAND" | grep -qE 'git push.*(main|develop)'; then
  echo "Error: main / develop への直接 push は禁止されています。" >&2
  echo "feature/* や fix/* などのブランチから PR を作成してください。" >&2
  exit 2
fi

exit 0
