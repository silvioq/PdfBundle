<?php

namespace Ps\PdfBundle\Tests\Templating;

use PHPUnit\Framework\TestCase;
use Ps\PdfBundle\Templating\ImageLocator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ImageLocatorTest extends TestCase
{
    private $kernel;
    private $locator;
    
    protected function setup(): void
    {
        $this->kernel = $this->getMockBuilder(Kernel::class)
                             ->setMethods(array('getBundle', 'registerBundles', 'registerContainerConfiguration', 'getRootDir'))
                             ->disableOriginalConstructor()
                             ->getMock();
                             
        $this->locator = new ImageLocator($this->kernel);
    }
    
    /**
     * @test
     * @dataProvider dataProvider
     */
    public function getImagePathSuccessfullyWhenBundleExists($bundleName, $imageName)
    {
        $bundle = $this->getMockBuilder(Bundle::class)
                       ->setMethods(array('getPath'))
                       ->disableOriginalConstructor()
                       ->getMock();
                       
        $bundlePath = 'some/bundle/path';
        
        $imageLogicalName = sprintf('%s:%s', $bundleName, $imageName);
        $expectedImagePath = $bundlePath.'/Resources/public/images/'.$imageName;

        $this->kernel->expects($this->once())
                     ->method('getBundle')
                     ->with($bundleName)
                     ->will($this->returnValue($bundle));
                     
        $bundle->expects($this->once())
               ->method('getPath')
               ->will($this->returnValue($bundlePath));
               
        $this->assertEquals($expectedImagePath, $this->locator->getImagePath($imageLogicalName));
    }
    
    public function dataProvider()
    {
        return array(
            array('SomeBundle', 'some-image.jpg'),
            array('SomeBundle', 'dir/some:image.jpg'),
        );
    }

    /**
     * @test
     */
    public function throwExceptionIfBundleDoesNotExist()
    {
        $this->kernel->expects($this->once())
                     ->method('getBundle')
                     ->will($this->throwException(new \InvalidArgumentException()));

        $this->expectException(\InvalidArgumentException::class);

        $this->locator->getImagePath('unexistedBundle:someImage.jpg');
    }
   
    /**
     * @test
     */
    public function getImagePathFromGlobalResourcesWhenBundleNameIsEmpty()
    {
        $rootDir = 'some/root/dir';
        $imageName = 'some/image/name.jpg';
        $prefixes = array('', ':', '::');
        
        $this->kernel->expects($this->exactly(count($prefixes)))
                     ->method('getRootDir')
                     ->will($this->returnValue($rootDir));
                     
        $this->kernel->expects($this->never())
                     ->method('getBundle');

        $expectedPath = $rootDir.'/Resources/public/images/'.$imageName;
        
        foreach($prefixes as $prefix)
        {
            $this->assertEquals($expectedPath, $this->locator->getImagePath($prefix.$imageName));
        }        
    }
}