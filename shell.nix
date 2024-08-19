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

  projectCgl = pkgs.writeShellApplication {
    name = "project-cgl";

    runtimeInputs = [
      php
    ];

    text = ''
      PHP_CS_FIXER_IGNORE_ENV=1 ./vendor/bin/php-cs-fixer fix --dry-run --diff
    '';
  };

  projectCglFix = pkgs.writeShellApplication {
    name = "project-cgl-fix";

    runtimeInputs = [
      php
    ];

    text = ''
      PHP_CS_FIXER_IGNORE_ENV=1 ./vendor/bin/php-cs-fixer fix
    '';
  };

in pkgs.mkShell {
  name = "TYPO3 Extension ThüCAT";
  buildInputs = [
    php
    composer
    projectInstall
    projectCgl
    projectCglFix
  ];

  shellHook = ''
    export PROJECT_ROOT="$(pwd)"
  '';
}
