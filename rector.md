# Rector Refactoring Report
*Generated: mar. 20 janv. 2026 12:30:41 WAT*


3 files with changes
====================

1) /home/andy-kani/pro/sites/packages/laravel-pawapay/src/Facades/PawaPay.php:1

    ---------- begin diff ----------
@@ @@
 <?php

+declare(strict_types=1);
+
 namespace PawaPay\Facades;

+use PawaPay\Services\PawaPayClient;
 use Illuminate\Support\Facades\Facade;

 /**
- * @method static \PawaPay\Services\PawaPayClient payIn(array $payload)
- * @method static \PawaPay\Services\PawaPayClient payOut(array $payload)
+ * @method static PawaPayClient payIn(array $payload)
+ * @method static PawaPayClient payOut(array $payload)
  * @method static mixed verify(string $transactionId)
  *
  * @see \PawaPay\Services\PawaPayClient
    ----------- end diff -----------

Applied rules:
 * DeclareStrictTypesRector


2) /home/andy-kani/pro/sites/packages/laravel-pawapay/src/PawaPayServiceProvider.php:21

    ---------- begin diff ----------
@@ @@
         );

         // Bind main client
-        $this->app->singleton('pawapay', function () {
+        $this->app->singleton('pawapay', function (): PawaPayClient {
             return new PawaPayClient(
                 config('pawapay.api_key'),
                 config('pawapay.base_url'),
    ----------- end diff -----------

Applied rules:
 * ClosureReturnTypeRector


3) /home/andy-kani/pro/sites/packages/laravel-pawapay/src/Services/PawapayClient.php:1

    ---------- begin diff ----------
@@ @@
 <?php

+declare(strict_types=1);
+
 namespace PawaPay\Services;

 use Illuminate\Http\Client\Response;
@@ @@
 class PawaPayClient
 {
     protected string $apiKey;
+
     protected string $baseUrl;
+
     protected int $timeout;

     public function __construct(
@@ @@
     {
         return $this->request(
             'GET',
-            "/transactions/{$transactionId}"
+            '/transactions/' . $transactionId
         );
     }

@@ @@

     /**
      * Add unique reference if missing.
+     * @param array<string, mixed> $payload
      */
     protected function withReference(array $payload): array
     {
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * EncapsedStringsToSprintfRector
 * AddParamArrayDocblockFromDimFetchAccessRector
 * DeclareStrictTypesRector


 [OK] 3 files would have been changed (dry-run) by Rector                                                               

