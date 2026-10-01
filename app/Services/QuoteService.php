<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class QuoteService
{
    public function update(): int
    {
        $url = config('services.quotes_api.url');

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->retry(3, 500)
                ->acceptJson()
                ->get($url);

            if ($response->failed()) {
                Log::warning('Falha HTTP na AwesomeAPI', ['status'=>$response->status()]);
                throw new RuntimeException('API retornou HTTP '.$response->status());
            }

            $data = $response->json();
            if (!is_array($data) || empty($data)) throw new RuntimeException('Resposta inválida da API.');

            $updated = 0;

            foreach ($data as $quote) {
                $code = strtoupper((string)$quote['code'] ?? '');

                if (!$code) continue;

                $asset = Asset::where('code', $code)->first();

                if (!$asset) {
                    Log::warning('Ativo não cadastrado', ['code' => $code]);
                    continue;
                }

                foreach (['bid', 'high', 'low', 'pctChange'] as $field) {
                    if (!array_key_exists($field, $quote)) {
                        Log::warning('Campo ausente', ['code' => $code, 'field' => $field]);
                        continue 2;
                    }
                }

                $asset->update([
                    'current_price' => (float)$quote['bid'],
                    'high_price'    => (float)$quote['high'],
                    'low_price'     => (float)$quote['low'],
                    'variation_24h' => (float)$quote['pctChange'],
                ]);

                $asset->priceHistories()->create([
                    'price'         => (float)$quote['bid'],
                    'high_price'    => (float)$quote['hight'],
                    'low_price'     => (float)$quote['low'],
                    'fetched_at'    => now(),
                ]);

                $updated++;
            }

            return $updated;
        } catch (\Throwable $e) {
            Log::error('Erro na atualização de cotações', ['message'=>$e->getMessage()]);
            throw $e;
        }
    }
}
