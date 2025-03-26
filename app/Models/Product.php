<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Customer;
use App\Models\ShelfRental;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Sale;
use App\Models\Label;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

     /**
     * 
     * Status:
     *
     *      1 = Entwurf
     *      2 = Aktiv
     *      3 = Zum Checkin freigegeben 
     *      4 = Verkauft
     * 
     */

    protected $fillable = [
        'customer_id', 
        'shelf_rental_id', 
        'name',
        'price',   
        'discount',   
        'discount_price',   
        'description',
        'size',
        'images',
        'category',
        'tags',
        'age_recommendation',
        'status',
        'cash_register_id',
        'views',
        'published_at',
    ];

    protected $casts = [
        'images' => 'array',
        'tags' => 'array',
    ];

     // Beziehung zu Category
     public function category()
     {
         return $this->belongsTo(Category::class);
     }

     
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class); 
    }

    
    public function shelfRental()
    {
        return $this->belongsTo(ShelfRental::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function labels()
    {
        return $this->hasMany(Label::class);
    }
    
    protected static function booted()
    {
        static::created(function ($product) {
            $product->generateLabel($product);
            $product->applyDiscount();
        });

        static::updated(function ($product) {
            $product->generateLabel($product);
            $product->applyDiscount();
            if ($product->isDirty('status')) {
                $product->deleteLike($product);
            }
        });
    }

    /**
     * Überprüft, ob die zugehörige Regalmiete einen Rabatt hat,
     * und berechnet den rabattierten Preis.
     */
    public function applyDiscount()
    {
        if ($this->shelfRental && $this->shelfRental->discount > 0) {
            $discountRate = $this->shelfRental->discount / 100;
            $this->discount = $this->shelfRental->discount; // Rabatt speichern
            $this->discount_price = round($this->price - ($this->price * $discountRate), 2);
        } else {
            $this->discount = 0;
            $this->discount_price = null;
        }

        // Speichern der Änderungen
        $this->saveQuietly();
    }

    public function deleteLike($product)
    {
        try {
            DB::table('liked_products')
                ->where('product_id', $product->id)
                ->delete();
            
            Log::info("Likes für Produkt-ID {$product->id} erfolgreich gelöscht.");
        } catch (\Exception $e) {
            Log::error("Fehler beim Löschen der Likes für Produkt-ID {$product->id}: " . $e->getMessage());
        }
    }

    public function generateLabel($product)
    {
        $label = Label::where('product_id', $product->id)->first();
        if ($label) {
            $label->update([
                'product_name' => $product->name,
                'shelve_id' => $product->shelfRental->shelf->id,
                'shelve_floor_number' => $product->shelfRental->shelf->floor_number,
                'price' => $product->price,
                'barcode' => $product->barcode ?? $this->generateUniqueEanCodeFromProductId($product->id),
            ]);
        } else {
            $label = Label::create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'shelve_id' => $product->shelfRental->shelf->id,
                'shelve_floor_number' => $product->shelfRental->shelf->floor_number,
                'price' => $product->price,
                'barcode' => $this->generateUniqueEanCodeFromProductId($product->id),
            ]);
        }
        return $label;
    }

    public function generateUniqueEanCodeFromProductId($productId)
    {
        $productIdStr = str_pad($productId, 12, '0', STR_PAD_LEFT);
        return $this->calculateEan13Checksum($productIdStr);
    }

    public function calculateEan13Checksum($eanCode)
    {
        if (strlen($eanCode) != 12) {
            throw new \InvalidArgumentException("EAN-Code muss genau 12 Ziffern lang sein.");
        }

        $digits = str_split($eanCode);
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += ($i % 2 == 0) ? $digits[$i] * 3 : $digits[$i];
        }

        $checksum = (10 - ($sum % 10)) % 10;
        return substr($eanCode . $checksum, 0, 12);
    }

    public function getImageUrl(int $index = 0, string $suffix = 'm'): string
    {
        $defaultImage = 'storage/site-images/default.webp';
        $images = json_decode($this->images);
    
        if (is_array($images) && isset($images[$index])) {
            $baseName = $images[$index];
            $imagePathWithSuffix = "storage/site-images/products/{$baseName}_{$suffix}.webp";
            return asset($imagePathWithSuffix);
        }
    
        return asset($defaultImage);
    }

    public function getAllImageUrls(string $suffix = 'm'): array
    {
        $defaultImage = 'storage/site-images/default.webp';
        $images = json_decode($this->images, true);

        // Wenn keine Bilder vorhanden sind, das Standardbild zurückgeben
        if (empty($images)) {
            return [];
        }

        // Array für die Bild-URLs vorbereiten
        $imageUrls = [];

        foreach ($images as $baseName) {
            // Pfad für jedes Bild mit Suffix und Dateiendung .webp
            $imagePathWithSuffix = "storage/site-images/products/{$baseName}_{$suffix}.webp";
            $imageUrls[] = asset($imagePathWithSuffix);
        }

        return $imageUrls;
    }

    public function deleteFromCashRegisterApi()
    {
        if (!$this->cash_register_id) {
            Log::warning("Produkt ID {$this->id} hat keine `cash_register_id`, daher wird es nicht aus der Kasse entfernt.");
            return false;
        }

        // API Einstellungen abrufen
        $apiSettings = Setting::where('type', 'api')
            ->where('key', 'like', 'cash_register%')
            ->pluck('value', 'key')
            ->mapWithKeys(fn($value, $key) => [$key => $value])
            ->toArray();

        $apiToken = $apiSettings['cash_register_api_key'] ?? null;
        $apiUrl = $apiSettings['cash_register_api_url'] ?? null;

        if (!$apiToken || !$apiUrl) {
            Log::error("Fehlende API-Einstellungen für das Kassensystem. Produkt {$this->id} konnte nicht gelöscht werden.");
            return false;
        }

        $client = new Client();
        $maxRetries = 30; 
        $retryDelay = 5; 
        $attempts = 0;

        do {
            $attempts++;

            try {
                $response = $client->delete($apiUrl . 'articles/' . (string) $this->cash_register_id, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiToken,
                        'Accept' => 'application/json',
                    ],
                ]);

                if ($response->getStatusCode() === 200) {
                    return true;
                } else {
                    return false;
                }
            } catch (ClientException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;

                if ($statusCode === 429) {
                    // 429 Too Many Requests -> Wiederholen mit Wartezeit
                    Log::warning("Rate-Limit erreicht beim Löschen von Produkt {$this->id}. Warte {$retryDelay} Sekunden...");

                    if ($attempts < $maxRetries) {
                        sleep($retryDelay);
                    } else {
                        Log::warning("Maximale Anzahl von Wiederholungen erreicht. Löschen von Produkt {$this->id} abgebrochen.");
                        return false;
                    }
                } else {
                    return false;
                }
            } catch (ServerException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;

                if ($statusCode >= 500 && $statusCode <= 599) {
                    // 500er-Fehler -> Kein Retry, direkt abbrechen
                    return false;
                }
            } catch (\Exception $e) {
                // Allgemeine Fehlerbehandlung
                Log::info("Unerwarteter Fehler beim Löschen von Produkt {$this->id}: " . $e->getMessage());
                return false;
            }
        } while ($attempts < $maxRetries);

        Log::error("Unbekannter Fehler beim Löschen von Produkt {$this->id}. Vorgang abgebrochen.");
        return false;
    }



    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    // Status-Methoden
    public function isDraft(): bool
    {
        return $this->status === '1';
    }

    public function isPublished(): bool
    {
        return $this->status === '2';
    }

    public function publish(): void
    {
        $this->update(['status' => '2']);
    }

    public function unpublish(): void
    {
        $this->update(['status' => '1']);
    }
}
