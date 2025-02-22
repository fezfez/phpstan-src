<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;

class PhpMethodFromParserNodeReflection extends PhpFunctionFromParserNodeReflection implements MethodReflection
{

	/** @var \PHPStan\Reflection\ClassReflection */
	private $declaringClass;

	/**
	 * @param ClassReflection $declaringClass
	 * @param ClassMethod $classMethod
	 * @param TemplateTypeMap $templateTypeMap
	 * @param \PHPStan\Type\Type[] $realParameterTypes
	 * @param \PHPStan\Type\Type[] $phpDocParameterTypes
	 * @param \PHPStan\Type\Type[] $realParameterDefaultValues
	 * @param bool $realReturnTypePresent
	 * @param Type $realReturnType
	 * @param Type|null $phpDocReturnType
	 * @param Type|null $throwType
	 * @param string|null $deprecatedDescription
	 * @param bool $isDeprecated
	 * @param bool $isInternal
	 * @param bool $isFinal
	 */
	public function __construct(
		ClassReflection $declaringClass,
		ClassMethod $classMethod,
		TemplateTypeMap $templateTypeMap,
		array $realParameterTypes,
		array $phpDocParameterTypes,
		array $realParameterDefaultValues,
		bool $realReturnTypePresent,
		Type $realReturnType,
		?Type $phpDocReturnType,
		?Type $throwType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
		bool $isFinal
	)
	{
		parent::__construct(
			$classMethod,
			$templateTypeMap,
			$realParameterTypes,
			$phpDocParameterTypes,
			$realParameterDefaultValues,
			$realReturnTypePresent,
			$realReturnType,
			$phpDocReturnType,
			$throwType,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$isFinal || $classMethod->isFinal()
		);
		$this->declaringClass = $declaringClass;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function getPrototype(): ClassMemberReflection
	{
		try {
			return $this->declaringClass->getNativeMethod($this->getClassMethod()->name->name)->getPrototype();
		} catch (\PHPStan\Reflection\MissingMethodFromReflectionException $e) {
			return $this;
		}
	}

	private function getClassMethod(): ClassMethod
	{
		/** @var \PhpParser\Node\Stmt\ClassMethod $functionLike */
		$functionLike = $this->getFunctionLike();
		return $functionLike;
	}

	public function isStatic(): bool
	{
		return $this->getClassMethod()->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->getClassMethod()->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->getClassMethod()->isPublic();
	}

	protected function getReturnType(): Type
	{
		$name = strtolower($this->getName());
		if (
			$name === '__construct'
			|| $name === '__destruct'
			|| $name === '__unset'
			|| $name === '__wakeup'
			|| $name === '__clone'
		) {
			return new VoidType();
		}
		if ($name === '__tostring') {
			return new StringType();
		}
		if ($name === '__isset') {
			return new BooleanType();
		}
		if ($name === '__sleep') {
			return new ArrayType(new IntegerType(), new StringType());
		}
		if ($name === '__set_state') {
			return new ObjectWithoutClassType();
		}

		return parent::getReturnType();
	}

	/** @return string|false */
	public function getDocComment()
	{
		return false;
	}

}
