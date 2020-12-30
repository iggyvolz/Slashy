<?php

return [
    "plugins" => [
        "vendor/phan/phan/.phan/plugins/UnknownElementTypePlugin.php",
        "vendor/phan/phan/.phan/plugins/UnreachableCodePlugin.php",
        "vendor/phan/phan/.phan/plugins/PrintfCheckerPlugin.php",
        "vendor/phan/phan/.phan/plugins/PregRegexCheckerPlugin.php",
        "vendor/phan/phan/.phan/plugins/UnusedSuppressionPlugin.php",
        "vendor/phan/phan/.phan/plugins/DuplicateArrayKeyPlugin.php"
    ],
    "strict_method_checking" => true,
    "strict_object_checking" => true,
    "strict_param_checking" => true,
    "strict_property_checking" => true,
    "strict_return_checking" => true,
    "constant_variable_detection" => true,
    "minimum_severity" => 0,
    'directory_list' => [ 'src', 'vendor' ],
    "exclude_analysis_directory_list" => [
        'vendor',
    ],
    "minimum_target_php_version" => "8.0",
    "suppress_issue_types" => [
        // https://github.com/phan/phan/issues/4334
        "PhanInvalidConstantExpression"
    ]
];