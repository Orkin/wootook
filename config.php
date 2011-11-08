<?php return array(
    'global' => array(
        'storyline' => array(
            'universe' => 'legacies',
            'episode'  => 'default',
            ),
        'web' => array(
            'base_url' => 'http://localhost/wootook/'
            ),
        'date' => array(
            'timezone' => 'Europe/Paris'
            ),
        'database' => array(
            'engine' => 'mysql',
            'options' => array(
                'hostname' => 'localhost',
                'username' => 'root',
                'password' => '',
                'database' => 'xnova'
                ),
            'table_prefix' => 'game_',
            ),
        'layout' => array(
            'page'   => 'page.php',
            'empire' => 'empire.php'
            ),
        'locales' => array(
            'fr'    => 'fr_FR',
            'fr_FR' => 'fr_FR',
            'en'    => 'en_US',
            'en_US' => 'en_US'
            )
        )
    );