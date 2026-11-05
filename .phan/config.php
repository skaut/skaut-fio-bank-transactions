<?php
/**
 * PHAN configuration
 *
 * @package skaut-fio-bank-transactions
 */

return array(
	'target_php_version'                        => '8.3',
	'minimum_target_php_version'                => '8.3',
	'backward_compatibility_checks'             => false, // Covered by PHPCS.
	'warn_about_undocumented_throw_statements'  => true,
	'strict_method_checking'                    => true,
	'strict_object_checking'                    => true,
	'strict_property_checking'                  => true,
	'strict_return_checking'                    => true,
	'constant_variable_detection'               => true,
	'redundant_condition_detection'             => true,
	'unused_variable_detection'                 => true,
	'warn_about_redundant_use_namespaced_class' => true,
	'directory_list'                            => array(
		'src',
		// 'tests',
		'.phan',
		'dist/vendor',
		'vendor/php-stubs/wordpress-stubs',
	),
	'file_list'                                 => array(
		'scoper.inc.php',
	),
	'exclude_analysis_directory_list'           => array(
		'dist/vendor/',
		'vendor/php-stubs/wordpress-stubs',
	),
	'suppress_issue_types'                      => array(
		'PhanPluginDuplicateConditionalNullCoalescing',
		'PhanPluginMixedKeyNoKey',
		// Classes register their hooks in the constructor and are instantiated only for that.
		'PhanNoopNew',
		// Hook callbacks are intentionally instance methods.
		'PhanPluginPossiblyStaticPublicMethod',
		'PhanPluginPossiblyStaticProtectedMethod',
		'PhanPluginPossiblyStaticPrivateMethod',
		'PhanPluginPossiblyStaticClosure',
		// False positive for the php-scoper prefixed dependencies.
		'PhanAccessMethodInternal',
	),
	'plugins'                                   => array(
		'AlwaysReturnPlugin',
		'DollarDollarPlugin',
		'DuplicateArrayKeyPlugin',
		'DuplicateExpressionPlugin',
		'EmptyStatementListPlugin',
		'InvalidVariableIssetPlugin',
		'NoAssertPlugin',
		'NonBoolBranchPlugin',
		'NonBoolInLogicalArithPlugin',
		'PossiblyStaticMethodPlugin',
		'PreferNamespaceUsePlugin',
		'PregRegexCheckerPlugin',
		'StrictComparisonPlugin',
		'SuspiciousParamOrderPlugin',
		'UnreachableCodePlugin',
		'UnusedSuppressionPlugin',
		'UseReturnValuePlugin',
	),
);
