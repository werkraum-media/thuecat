{ pkgs ? import <nixpkgs> { } }:

let
  php = pkgs.php82.buildEnv {
    extensions = { enabled, all }: enabled ++ (with all; [
      xdebug
    ]);

    extraConfig = ''
      xdebug.mode = debug
      memory_limit = 4G
    '';
  };
  inherit(pkgs.php82Packages) composer;

  projectInstall = pkgs.writeShellApplication {
    name = "project-install";
    runtimeInputs = [
      php
      composer
    ];
    text = ''
      rm -rf .Build/ vendor/ composer.lock
      composer update --prefer-dist --no-progress --working-dir="$PROJECT_ROOT"
    '';
  };
  projectTestAcceptance = pkgs.writeShellApplication {
    name = "project-test-acceptance";
    runtimeInputs = [
      projectInstall
      pkgs.sqlite
      pkgs.firefox
      pkgs.geckodriver
      php
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
    php
    composer
    projectInstall
    projectTestAcceptance
  ];

  shellHook = ''
    export PROJECT_ROOT="$(pwd)"
  '';
}
