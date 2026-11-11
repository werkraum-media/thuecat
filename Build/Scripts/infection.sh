#!/bin/bash

# Usage: Build/Scripts/infection.sh [--full] [infection options]
#
# Reads the coverage of all suites from .coverage/ (coverage-xml, junit.xml),
# which the caller produces: a per-suite report counts mutants as escaped that
# the other suite kills.
#
# Without --full: the gate for new code. Lines changed against INFECTION_BASE
# (default origin/main) only, uncovered code counted, 100% MSI, no skipped
# mutant, INFECTION_GATE_TIMEOUT per mutant (default 600 s).
# With --full: everything, the configured timeout, no threshold; over the whole
# package (no further options) the totals are checked against
# Build/quality-baseline.json.
# INFECTION_THREADS sets the threads (default max).

cd "$(dirname "$0")/../.." || exit 1

gate="1"
arguments=()
for argument in "$@"; do
  if [[ ${argument} == "--full" ]]; then
    gate=""
  else
    arguments+=("${argument}")
  fi
done

summary="var/infection/summary.json"
rm -f "${summary}"

# A mutant is skipped when its covering tests outrun the timeout, leaving it
# undecided. The gate has few mutants, so it can afford a timeout that lets
# them finish. Infection resolves the config's paths against its directory,
# so the copy sits beside the original.
scope=()
if [[ ${gate} ]]; then
  trap 'rm -f infection.gate.json' EXIT
  INFECTION_GATE_TIMEOUT="${INFECTION_GATE_TIMEOUT:-600}" php -r '
    require "vendor/autoload.php";
    $config = \ColinODell\Json5\Json5Decoder::decode(file_get_contents("infection.json5"), true);
    $config["timeout"] = (int)getenv("INFECTION_GATE_TIMEOUT");
    file_put_contents("infection.gate.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  ' || exit $?
  scope=(--configuration=infection.gate.json --git-diff-lines --git-diff-base="${INFECTION_BASE:-origin/main}"
    --with-uncovered --min-msi=100 --min-covered-msi=100)
fi

# Whole covering test classes per mutant would mean every functional class
# that touches the import, so only the covering test cases run.
command=(vendor/bin/infection --coverage=.coverage --skip-initial-tests --only-covering-test-cases
  --threads="${INFECTION_THREADS:-max}" --logger-summary-json="${summary}" "${scope[@]}" "${arguments[@]}")
echo "executing ${command[*]}"
"${command[@]}"
status=$?

# Skipped mutants are left out of the MSI, so the threshold alone passes them.
if [[ ${gate} && -f ${summary} ]]; then
  skipped=$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["stats"]["skippedCount"] ?? 0;' "${summary}")
  if [[ ${skipped} -gt 0 ]]; then
    echo "${skipped} mutant(s) skipped: their covering tests outran ${INFECTION_GATE_TIMEOUT:-600} s"
    exit 1
  fi
fi

# A filtered run's MSI is not the package total.
if [[ ! ${gate} && ${#arguments[@]} -eq 0 && ${status} -eq 0 ]]; then
  php Build/Scripts/check-quality-baseline.php "${summary}" || exit $?
fi

exit ${status}
