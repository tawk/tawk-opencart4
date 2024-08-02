<?php declare(strict_types=1);

namespace Tawk\Test\Coverages;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Widget Selection Test')]
class WidgetSelectionTest extends TestCase {

	#[Test]
	#[Group('dummy_test')]
	public function dummy_test(): void {
		$a = "hello";
		$this->assertSame("hello", $a);
	}
}