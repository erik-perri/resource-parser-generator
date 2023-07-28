#!/usr/bin/env bash
# This script runs all of the `COMMANDS` in the background then waits for all of the processes to finish. Once they are
# complete, the script will output all of the logs and exit with a failure status code if any failed.

COMMANDS=(
  "composer analyze"
  "composer lint"
  "composer test -- --coverage-html=coverage --coverage-text"
  "composer validate --strict"
)

declare -a PROCESS_IDS
declare -a OUTPUT_FILES

for COMMAND in "${COMMANDS[@]}"; do
  OUTPUT_FILES+=("$(mktemp)")
  $COMMAND > "${OUTPUT_FILES[-1]}" 2>&1 & PROCESS_IDS+=($!)
done

EXIT_CODE=0

for ((i = 0; i < ${#PROCESS_IDS[@]}; ++i)); do
  if ! wait "${PROCESS_IDS[i]}"; then
    EXIT_CODE=1
  fi

  cat "${OUTPUT_FILES[i]}"
  rm -f "${OUTPUT_FILES[i]}"
done

exit "$EXIT_CODE"
