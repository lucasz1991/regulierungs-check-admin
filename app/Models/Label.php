<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\Renderers\ImageRenderer;
use Picqer\Barcode\Renderers\BarcodeImageGenerator;
use Picqer\Barcode\BarcodeGeneratorPNG;


class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_name',
        'shelve_id',
        'shelve_floor_number',
        'price',
        'barcode',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shelve()
    {
        return $this->belongsTo(Shelve::class);
    }

    public function generateBarcode($barcode)
    {
        // Create a barcode generator instance
        $generator = new BarcodeGeneratorPNG();  // This generates PNG images
        $barcodeImage = $generator->getBarcode($barcode, BarcodeGeneratorPNG::TYPE_CODE_128_C);
         // Base64-codiertes Bild erstellen
        $base64Image = 'data:image/png;base64,' . base64_encode($barcodeImage);

        return $base64Image; 
    }
}

