{{-- GEO 预留：后续用你们的 GEO 包替换本文件内容即可 --}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => '有机硅商城',
    'url' => config('app.url'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => config('app.url').'/search?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
