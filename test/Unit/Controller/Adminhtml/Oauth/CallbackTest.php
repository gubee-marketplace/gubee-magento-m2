<?php

declare(strict_types=1);

namespace Gubee\Integration\Test\Unit\Controller\Adminhtml\Oauth;

use Gubee\Integration\Controller\Adminhtml\Oauth\Callback;
use Gubee\Integration\Service\Gubee\OAuth;
use Gubee\Integration\Service\Gubee\OAuthException;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

class CallbackTest extends TestCase
{
    public function testCallbackConsumesAttemptAndRejectsReplay(): void
    {
        $controller    = (new ReflectionClass(Callback::class))->newInstanceWithoutConstructor();
        $storedAttempt = ['state' => 'expected'];
        $session       = $this->createMock(Session::class);
        $session->expects($this->exactly(2))->method('getData')->with('gubee_oauth_attempt', true)
            ->willReturnCallback(function () use (&$storedAttempt) {
                $attempt       = $storedAttempt;
                $storedAttempt = null;
                return $attempt;
            });
        $oauth = $this->createMock(OAuth::class);
        $calls = 0;
        $oauth->expects($this->exactly(2))->method('completeAuthorization')
            ->willReturnCallback(function ($attempt, $state, $code, $adminId) use (&$calls, &$storedAttempt) {
                self::assertNull($storedAttempt);
                self::assertSame('expected', $state);
                self::assertSame('returned-code', $code);
                self::assertSame(7, $adminId);
                if ($calls++ === 0) {
                    self::assertSame(['state' => 'expected'], $attempt);
                    return;
                }
                self::assertSame([], $attempt);
                throw new OAuthException('Invalid or expired authorization attempt.');
            });
        $request = $this->createMock(Request::class);
        $request->method('getQuery')->willReturnMap([
            ['state', null, 'expected'],
            ['code', null, 'returned-code'],
            ['error', null, null],
        ]);
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(7);
        $auth = $this->createMock(Auth::class);
        $auth->method('getUser')->willReturn($user);
        $messages = $this->createMock(ManagerInterface::class);
        $messages->expects($this->once())->method('addSuccessMessage');
        $messages->expects($this->once())->method('addErrorMessage');
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->exactly(2))->method('setPath')
            ->with('adminhtml/system_config/edit', ['section' => 'gubee'])->willReturnSelf();
        $factory = $this->getMockBuilder(RedirectFactory::class)->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();
        $factory->method('create')->willReturn($redirect);
        foreach (
            [
                'oauth'                 => $oauth,
                '_session'              => $session,
                '_request'              => $request,
                '_auth'                 => $auth,
                '_response'             => $this->createMock(Response::class),
                'messageManager'        => $messages,
                'resultRedirectFactory' => $factory,
            ] as $name => $value
        ) {
            $property = new ReflectionProperty(Callback::class, $name);
            $property->setAccessible(true);
            $property->setValue($controller, $value);
        }
        self::assertSame($redirect, $controller->execute());
        self::assertSame($redirect, $controller->execute());
    }

    public function testCallbackAcceptsFixedUrlWithoutAdminSecretKey(): void
    {
        $controller = (new ReflectionClass(Callback::class))->newInstanceWithoutConstructor();
        $auth       = $this->createMock(Auth::class);
        $auth->method('isLoggedIn')->willReturn(true);
        $request = $this->createMock(Request::class);
        $request->method('isPost')->willReturn(false);
        $request->method('getActionName')->willReturn('callback');
        $backendUrl = $this->createMock(UrlInterface::class);
        $backendUrl->method('useSecretKey')->willReturn(true);
        foreach (['_auth' => $auth, '_request' => $request, '_backendUrl' => $backendUrl] as $name => $value) {
            $property = new ReflectionProperty(Callback::class, $name);
            $property->setAccessible(true);
            $property->setValue($controller, $value);
        }
        self::assertTrue($controller->_processUrlKeys());
    }
}
