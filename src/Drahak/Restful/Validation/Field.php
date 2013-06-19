<?php
namespace Drahak\Restful\Validation;

use Nette\Object;
use Nette\Utils\Validators;

/**
 * Validation field
 * @package Drahak\Restful\Validation
 * @author Drahomír Hanák
 *
 * @property-read string $name
 * @property-read Rule[] $rules
 * @property-read IValidator $validator
 */
class Field extends Object implements IField
{

	/** @var array Default field error messages for validator */
	public static $defaultMessages = array(
		IValidator::EQUAL => 'Please enter %s.',
		IValidator::MIN_LENGTH => 'Please enter a value of at least %d characters.',
		IValidator::MAX_LENGTH => 'Please enter a value no longer than %d characters.',
		IValidator::LENGTH => 'Please enter a value between %d and %d characters long.',
		IValidator::EMAIL => 'Please enter a valid email address.',
		IValidator::URL => 'Please enter a valid URL.',
		IValidator::INTEGER => 'Please enter a numeric value.',
		IValidator::FLOAT => 'Please enter a numeric value.',
		IValidator::RANGE => 'Please enter a value between %d and %d.'
	);

	/** @var array Numeric expressions that needs to convert value from string (because of x-www-form-urlencoded) */
	protected static $numericExpressions = array(
		IValidator::INTEGER, IValidator::FLOAT, IValidator::NUMERIC, IValidator::RANGE
	);

	/** @var Rule[] */
	private $rules = array();

	/** @var IValidator */
	private $validator;

	/** @var string */
	private $name;

	/**
	 * @param string $name
	 * @param IValidator $validator
	 */
	public function __construct($name, IValidator $validator)
	{
		$this->name = $name;
		$this->validator = $validator;
	}

	/**
	 * Add validation rule for this field
	 * @param string $expression
	 * @param string|null $message
	 * @param mixed|null $argument
	 * @return Field
	 */
	public function addRule($expression, $message = NULL, $argument = NULL)
	{
		$rule = new Rule;
		$rule->field = $this->name;
		$rule->expression = $expression;
		$rule->message = $message;
		$rule->argument = $argument;

		if ($message === NULL && isset(self::$defaultMessages[$expression])) {
			$rule->message = self::$defaultMessages[$expression];
		}

		$this->rules[] = $rule;
		return $this;
	}

	/**
	 * Validate field for given value
	 * @param mixed $value
	 * @return array
	 */
	public function validate($value)
	{
		$errors = array();
		foreach ($this->rules as $rule) {
			try {
				if (in_array($rule->expression, static::$numericExpressions)) {
					$value = $this->parseNumericValue($value);
				}

				$this->validator->validate($value, $rule);
			} catch (ValidationException $e) {
				$errors[] = array(
					'field' => $this->getName(),
					'message' => vsprintf($rule->getMessage(), $rule->getArgument()),
					'code' => $rule->getCode()
				);
			}
		}
		return $errors;
	}

	/**
	 * Get field case-sensitive name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get field rules
	 * @return Rule[]
	 */
	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * Get validator
	 * @return IValidator
	 */
	public function getValidator()
	{
		return $this->validator;
	}

	/**
	 * Convert string -> int, string -> float because of textual x-www-form-data
	 * @param mixed $value
	 * @return mixed
	 */
	protected function parseNumericValue($value)
	{
		if (Validators::isNumericInt($value)) return (int)$value;
		if (Validators::isNumeric($value)) return (float)$value;
		return $value;
	}

}