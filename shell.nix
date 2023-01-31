{ pkgs ? import <nixpkgs> { } }:

let
  projectInstall = pkgs.writeShellApplication {
    name = "project-install";
    runtimeInputs = [
      pkgs.php82
      pkgs.php82Packages.composer
    ];
    text = ''
      composer install --prefer-dist --no-progress --working-dir="$PROJECT_ROOT"
    '';
  };
  projectTestAcceptance = pkgs.writeShellApplication {
    name = "project-test-acceptance";
    runtimeInputs = [
      projectInstall
      pkgs.sqlite
      pkgs.firefox
      pkgs.geckodriver
      pkgs.php82
    ];
    text = ''
      project-install

      export INSTANCE_PATH="$PROJECT_ROOT/.Build/web/typo3temp/var/tests/acceptance"
      export typo3DatabaseDriver=pdo_sqlite

      mkdir -p "$INSTANCE_PATH"
      ./vendor/bin/codecept run
    '';
  };

in pkgs.mkShell {
  name = "TYPO3 Extension ThüCAT";
  buildInputs = [
    projectTestAcceptance
  ];

  shellHook = ''
    export PROJECT_ROOT="$(pwd)"
  '';
}
