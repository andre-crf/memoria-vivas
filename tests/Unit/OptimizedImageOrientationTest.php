<?php

namespace Tests\Unit;

use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Support\OptimizedImageVersions;
use GdImage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OptimizedImageOrientationTest extends TestCase
{
    /**
     * @return array<string, array{int, list<list<string>>, list<list<string>>}>
     */
    public static function exifOrientations(): array
    {
        return [
            'normal' => [1, [['A', 'B', 'C'], ['D', 'E', 'F']], [['A', 'B', 'C'], ['D', 'E', 'F']]],
            'mirror horizontal' => [2, [['A', 'B', 'C'], ['D', 'E', 'F']], [['C', 'B', 'A'], ['F', 'E', 'D']]],
            'rotate 180' => [3, [['A', 'B', 'C'], ['D', 'E', 'F']], [['F', 'E', 'D'], ['C', 'B', 'A']]],
            'mirror vertical' => [4, [['A', 'B', 'C'], ['D', 'E', 'F']], [['D', 'E', 'F'], ['A', 'B', 'C']]],
            'transpose' => [5, [['A', 'B', 'C'], ['D', 'E', 'F']], [['A', 'D'], ['B', 'E'], ['C', 'F']]],
            'rotate 90 clockwise' => [6, [['A', 'B', 'C'], ['D', 'E', 'F']], [['D', 'A'], ['E', 'B'], ['F', 'C']]],
            'transverse' => [7, [['A', 'B', 'C'], ['D', 'E', 'F']], [['F', 'C'], ['E', 'B'], ['D', 'A']]],
            'rotate 90 counterclockwise' => [8, [['A', 'B', 'C'], ['D', 'E', 'F']], [['C', 'F'], ['B', 'E'], ['A', 'D']]],
        ];
    }

    /**
     * @param  list<list<int>>  $source
     * @param  list<list<string>>  $expected
     */
    #[DataProvider('exifOrientations')]
    public function test_gd_applies_each_exif_orientation(int $orientation, array $source, array $expected): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('A extensão GD é necessária para testar a orientação EXIF.');
        }

        $image = imagecreatetruecolor(3, 2);
        $colors = [
            'A' => imagecolorallocate($image, 255, 0, 0),
            'B' => imagecolorallocate($image, 0, 255, 0),
            'C' => imagecolorallocate($image, 0, 0, 255),
            'D' => imagecolorallocate($image, 255, 255, 0),
            'E' => imagecolorallocate($image, 0, 255, 255),
            'F' => imagecolorallocate($image, 255, 0, 255),
        ];

        foreach ($source as $y => $row) {
            foreach ($row as $x => $label) {
                imagesetpixel($image, $x, $y, $colors[$label]);
            }
        }

        $processor = new OptimizedImageVersions(\Mockery::mock(ArquivoStorage::class));
        $method = new \ReflectionMethod($processor, 'orientGdByExifOrientation');
        $result = $method->invoke($processor, $image, $orientation);

        $this->assertInstanceOf(GdImage::class, $result);
        $this->assertSame(count($expected[0]), imagesx($result));
        $this->assertSame(count($expected), imagesy($result));

        foreach ($expected as $y => $row) {
            foreach ($row as $x => $label) {
                $this->assertSame($colors[$label], imagecolorat($result, $x, $y), "Pixel {$label} incorreto na orientação {$orientation}.");
            }
        }

        imagedestroy($result);
        if ($result !== $image) {
            imagedestroy($image);
        }
    }
}
