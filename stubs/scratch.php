<?php

return [
    /**
     * The book title.
     */
    'title' => 'Example Book',

    /**
     * The author name.
     */
    'author' => 'Bhushan Gaikwad',

    /**
     * The list of fonts to be used in the different themes.
     */
    'fonts' => [
        //        'calibri' => 'Calibri-Regular.ttf',
        //        'times' => 'times-regular.ttf',
    ],

    /**
     * Document Dimensions.
     */
    'document' => [
        'format' => [210, 297],
        'margin_left' => 27,
        'margin_right' => 27,
        'margin_bottom' => 14,
        'margin_top' => 14,
    ],

    /**
     * Table of Contents Levels
     */
    'toc_levels' => [
        'H1' => 0,
        'H2' => 1,
        'H3' => 2,
    ],

    /**
     * Cover photo position and dimensions
     */
    'cover' => [
        'position' => 'position: absolute; left:0; right: 0; top: -.2; bottom: 0;',
        'dimensions' => 'width: 210mm; height: 297mm; margin: 0;',
    ],
    /**
     * Make commonmark configurable with Callable
     */
    'configure_commonmark' => false,

    /**
     * Page ranges to be used with the sample command.
     */
    'sample' => [
        [1, 3],
        [80, 85],
        [100, 103],
    ],

    /**
     * A notice printed at the final page of a generated sample.
     */
    'sample_notice' => '<p style="text-align: center;">This is a sample page of a generated PDF document.</p>',
];
