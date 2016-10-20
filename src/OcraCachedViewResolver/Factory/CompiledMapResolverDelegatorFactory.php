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

namespace OcraCachedViewResolver\Factory;

use Interop\Container\ContainerInterface;
use OcraCachedViewResolver\Module;
use OcraCachedViewResolver\View\Resolver\CachingMapResolver;
use OcraCachedViewResolver\View\Resolver\LazyResolver;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Resolver\AggregateResolver;

/**
 * Factory responsible of building a {@see \Zend\View\Resolver\TemplateMapResolver}
 * from cached template definitions
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
final class CompiledMapResolverDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return AggregateResolver
     */
    public function createDelegatorWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName, $callback)
    {
        return $this->create($serviceLocator, $callback);
    }

    /**
     * A factory that creates delegates of a given service
     *
     * @param  ContainerInterface $container
     * @param  string             $name
     * @param  callable           $callback
     * @param  null|array         $options
     * @return AggregateResolver
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        return $this->create($container, $callback);
    }

    /**
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @param callable $callback
     * @return AggregateResolver
     */
    protected function create($container, callable $callback)
    {
        if (!$container instanceof ContainerInterface && !$container instanceof ServiceLocatorInterface) {
            throw new \InvalidArgumentException('Invalid container');
        }

        $config = $container->get('config')[Module::CONFIG];
        /* @var $cache \Zend\Cache\Storage\StorageInterface */
        $cache  = $container->get($config[Module::CONFIG_CACHE_SERVICE]);

        $resolver = new AggregateResolver();

        $resolver->attach(new LazyResolver($callback), 50);
        $resolver->attach(new CachingMapResolver($cache, $config[Module::CONFIG_CACHE_KEY], $callback), 100);

        return $resolver;
    }
}
