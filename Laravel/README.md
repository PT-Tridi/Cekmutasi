# Cekmutasi X Laravel Framework
Development &amp; Integration Toolkit for Laravel Framework

## Steps
- Copy **packages** folder under your laravel folder, or you can skip to **packages/pt-tridi** if **packages** folder already exists (http://prntscr.com/ksezq1)
- Open your **config/app.php** and add this code to the providers array, it will looks like:

<pre><code>'providers' =&gt; [

      // other providers

      PTTridi\Cekmutasi\CekmutasiServiceProvider::class,

],</code></pre>

Add this code to your class aliases array

<pre><code>'aliases' =&gt; [

      // other aliases

      'Cekmutasi' => PTTridi\Cekmutasi\CekmutasiFacade::class,

],</code></pre>

Open your **composer.json** in the root folder then add this code to the psr-4 on autoload section, it will looks like:

<pre><code>&quot;autoload&quot;: {

        // other section
        
        &quot;psr-4&quot;: {
            &quot;App\\&quot;: &quot;app/&quot;,
            &quot;PTTridi\\Cekmutasi\\&quot;: &quot;packages/pt-tridi/cekmutasi/src&quot;,
        },
        
       // other section
       
},</code></pre>

then run composer command on your Command Line Console

<pre><code>composer dump-autoload</code></pre>

## How To Use?

Edit and set your Api Key on **packages/pt-tridi/cekmutasi/src/Cekmutasi.php**

You can use cekmutasi library by importing cekmutasi class. Here is the example of using cekmutasi class on Controller

<pre><code>&#x3C;?php

namespace App\Http\Controllers;

use Cekmutasi;

class AnotherController extends Controller
{
    $mutation = Cekmutasi::bank()-&#x3E;mutation($searchOptions);

    dd($mutations);
}

?&#x3E;</code></pre>

For further example, you can check out in TestCekmutasi.php included on this package
