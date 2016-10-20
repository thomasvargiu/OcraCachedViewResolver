<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace OcraCachedViewResolverTest\View\Resolver;

use OcraCachedViewResolver\Factory\CompiledMapResolverDelegatorFactory;
use OcraCachedViewResolver\Module;
use OcraCachedViewResolver\View\Resolver\CachingMapResolver;
use OcraCachedViewResolver\View\Resolver\LazyResolver;
use PHPUnit_Framework_TestCase;
use stdClass;
use Zend\Cache\Storage\StorageInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\TemplateMapResolver;

/**
 * Tests for {@see \OcraCachedViewResolver\Factory\CompiledMapResolverDelegatorFactory}
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @group Coverage
 *
 * @covers \OcraCachedViewResolver\Factory\CompiledMapResolverDelegatorFactory
 */
class CompiledMapResolverDelegatorFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceLocatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $locator;

    /**
     * @var callable|\PHPUnit_Framework_MockObject_MockObject
     */
    private $callback;

    /**
     * @var StorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->locator  = $this->getMock(ServiceLocatorInterface::class);
        $this->callback = $this->getMock(stdClass::class, ['__invoke']);
        $this->cache    = $this->getMock(StorageInterface::class);

        $this->locator->expects($this->any())->method('get')->will($this->returnValueMap([
            [
                'config',
                [
                    Module::CONFIG => [
                        Module::CONFIG_CACHE_KEY     => 'key-name',
                        Module::CONFIG_CACHE_SERVICE => 'cache_name',
                    ],
                ],
            ],
            [
                'cache_name',
                $this->cache,
            ],
        ]));
    }

    public function testCreateServiceWithExistingCachedTemplateMap()
    {
        $this
            ->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('key-name')
            ->will($this->returnValue(['foo' => 'bar']));

        $this->callback->expects($this->never())->method('__invoke');

        $factory  = new CompiledMapResolverDelegatorFactory();
        $resolver = $factory->createDelegatorWithName($this->locator, 'resolver', 'resolver', $this->callback);

        $this->assertInstanceOf(AggregateResolver::class, $resolver);

        $resolvers = $resolver->getIterator()->toArray();

        $this->assertInstanceOf(LazyResolver::class, $resolvers[0]);
        $this->assertInstanceOf(CachingMapResolver::class, $resolvers[1]);

        $this->assertSame('bar', $resolver->resolve('foo'));
    }

    public function testCreateServiceWithEmptyCachedTemplateMap()
    {
        $realResolver = new TemplateMapResolver(['bar' => 'baz']);

        $this->cache->expects($this->once())->method('getItem')->with('key-name')->will($this->returnValue(null));
        $this->cache->expects($this->once())->method('setItem')->with('key-name', ['bar' => 'baz']);
        $this->callback->expects($this->once())->method('__invoke')->will($this->returnValue($realResolver));

        $factory  = new CompiledMapResolverDelegatorFactory();
        $resolver = $factory->createDelegatorWithName($this->locator, 'resolver', 'resolver', $this->callback);

        $this->assertInstanceOf(AggregateResolver::class, $resolver);

        $resolvers = $resolver->getIterator()->toArray();

        $this->assertInstanceOf(LazyResolver::class, $resolvers[0]);
        $this->assertInstanceOf(CachingMapResolver::class, $resolvers[1]);

        $this->assertSame('baz', $resolver->resolve('bar'));
    }
}
