<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit664e7b08052adc793a6f3aaa24a8fdbf
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'Linups\\LinupsFirewall\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Linups\\LinupsFirewall\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit664e7b08052adc793a6f3aaa24a8fdbf::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit664e7b08052adc793a6f3aaa24a8fdbf::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit664e7b08052adc793a6f3aaa24a8fdbf::$classMap;

        }, null, ClassLoader::class);
    }
}
