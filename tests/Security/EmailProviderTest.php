<?php

declare(strict_types=1);

/*
 * This file is part of the NucleosUserBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserBundle\Tests\Security;

use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\Model\UserManagerInterface;
use Nucleos\UserBundle\Security\EmailProvider;
use Nucleos\UserBundle\Tests\App\Entity\TestUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

final class EmailProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    private MockObject|UserManagerInterface $userManager;

    private EmailProvider $userProvider;

    protected function setUp(): void
    {
        $this->userManager  = $this->getMockBuilder(UserManagerInterface::class)->getMock();
        $this->userProvider = new EmailProvider($this->userManager);
    }

    public function testLoadUserByUsername(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $this->userManager->expects(static::once())
            ->method('findUserByEmail')
            ->with('foobar')
            ->willReturn($user)
        ;

        static::assertSame($user, $this->userProvider->loadUserByUsername('foobar'));
    }

    public function testLoadUserByInvalidUsername(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->userManager->expects(static::once())
            ->method('findUserByEmail')
            ->with('foobar')
            ->willReturn(null)
        ;

        $this->userProvider->loadUserByUsername('foobar');
    }

    public function testRefreshUserBy(): void
    {
        $user = $this->createUser();

        $refreshedUser = $this->getMockBuilder(UserInterface::class)->getMock();
        $this->userManager->expects(static::once())
            ->method('findUserByEmail')
            ->with('123')
            ->willReturn($refreshedUser)
        ;

        $this->userManager->expects(static::atLeastOnce())
            ->method('getClass')
            ->willReturn(\get_class($user))
        ;

        static::assertSame($refreshedUser, $this->userProvider->refreshUser($user));
    }

    public function testRefreshDeleted(): void
    {
        $this->expectException(AuthenticationException::class);

        $user = $this->createUser();
        $this->userManager->expects(static::once())
            ->method('findUserByEmail')
            ->willReturn(null)
        ;

        $this->userManager->expects(static::atLeastOnce())
            ->method('getClass')
            ->willReturn(\get_class($user))
        ;

        $this->userProvider->refreshUser($user);
    }

    public function testRefreshInvalidUser(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $user = $this->getMockBuilder(SymfonyUserInterface::class)->getMock();
        $this->userManager
            ->method('getClass')
            ->willReturn(\get_class($user))
        ;

        $this->userProvider->refreshUser($user);
    }

    public function testRefreshInvalidUserClass(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $user         = $this->createUser();
        $providedUser = $this->getMockBuilder(TestUser::class)->getMock();

        $this->userManager->expects(static::atLeastOnce())
            ->method('getClass')
            ->willReturn(\get_class($user))
        ;

        $this->userProvider->refreshUser($providedUser);
    }

    /**
     * @return UserInterface&MockObject
     */
    private function createUser(): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');

        return $user;
    }
}
