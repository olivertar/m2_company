<?php
/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Orangecat\Company\Test\Unit\Plugin\Customer\Controller\Account;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Exception\NotFoundException;
use Orangecat\Company\Model\Config;
use Orangecat\Company\Plugin\Customer\Controller\Account\RegistrationRestriction;
use PHPUnit\Framework\TestCase;

class RegistrationRestrictionTest extends TestCase
{
    private RegistrationRestriction $plugin;
    private Config $configMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->plugin = new RegistrationRestriction($this->configMock);
    }

    public function testBeforeExecuteThrowsWhenRegistrationNotAllowed(): void
    {
        $subjectMock = $this->createMock(ActionInterface::class);

        $this->configMock->expects($this->once())
            ->method('isFrontendCustomerRegistrationAllowed')
            ->willReturn(false);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Page not found.');

        $this->plugin->beforeExecute($subjectMock);
    }

    public function testBeforeExecuteAllowsWhenRegistrationAllowed(): void
    {
        $subjectMock = $this->createMock(ActionInterface::class);

        $this->configMock->expects($this->once())
            ->method('isFrontendCustomerRegistrationAllowed')
            ->willReturn(true);

        // No exception expected
        $this->plugin->beforeExecute($subjectMock);
        $this->addToAssertionCount(1);
    }
}
