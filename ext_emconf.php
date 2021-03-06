<?php

########################################################################
# Extension Manager/Repository config file for ext "bynder".
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'Bynder integration for TYPO3',
    'description' => 'Integrate the Bynder DAM into TYPO3',
    'category' => 'distribution',
    'version' => '0.0.3-dev',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'author' => 'Frans Saris - Beech.it',
    'author_email' => 't3ext@beech.it',
    'constraints' => [
        'depends' => [
            'typo3' => '>= 10.4.13',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
