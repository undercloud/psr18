# PSR-18 HTTP Client

[![Build Status](https://travis-ci.org/undercloud/psr18.svg?branch=master)](https://travis-ci.org/undercloud/psr18)

Implementation of https://www.php-fig.org/psr/psr-18/

## Features
* PSR-18 compatible
* Supports of any type of HTTP requests
* JSON requests
* Body with multipart/form-data
* Upload / Download huge files
* SSL / TLS

## Requirements
PHP 7.1+

## License
MIT

## Install
`composer require undercloud/psr18`

## Usage

```php
$responsePrototype = new Zend\Diactoros\Response;

$httpClient = new Undercloud\Psr18\HttpClient($responsePrototype, [
    'timeout' => 10,
    'ssl' => [
        'verifyPeer' => false,
        'verifyPeerName' => false
    ]
]);

$requestPrototype = new Zend\Diactoros\Request('https://your-domain-name.com/post-data','POST');

$body = new Undercloud\Psr18\Streams\JsonStream([
    'foo' => 'bar'
]);
// or
$body = new Undercloud\Psr18\Streams\MultipartStream([
    'title' => 'Summer 69',
    'description' => 'Hey check it out...',
    'tags => ['summer','beach','sea'],
    'photo' => new Undercloud\Psr18\Streams\FileStream(
        $pathToFile
    )
]);

$requestPrototype = $requestPrototype->withBody($body);

$responsePrototype = $httpClient->sendRequest($requestPrototype);
```

## Streams

### TextStream
```php
// simple text stream
new Undercloud\Psr18\Streams\TextStream('PHP7 is Awesome')

// with options
$base64 = base64_encode('PHP7 is Awesome');

new Undercloud\Psr18\Streams\TextStream($base64, [
    'mime' => 'text/plain'
    'encoding' => 'base64'
])

// URL encode
$urlencode = urlencode('PHP7 is Awesome');

new Undercloud\Psr18\Streams\TextStream($urlencode);
```

### JsonStream

```php
<code>
  JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
</code>

$data = [
    'foo' => 'bar'  
];

$jsonStream = new Undercloud\Psr18\Streams\JsonStream($data, $encodingOptions = 79)

// {"foo":"bar"}
$jsonStream->getContents();
```

### SocketStream
```php
// fopen
// stream_socket_client

new Undercloud\Psr18\Streams\SocketStream($resource);
```

### MultipartStream

```php
new Undercloud\Psr18\Streams\MultipartStream([
    'simple' => 'text',
    'array' => ['foo','bar','baz'],
    'base64' => => new Undercloud\Psr18\Streams\TextStream(
        'iVBORw0KGgoAAAA...', [
            'mime' => 'image/png',
            'encoding' => 'base64'
        ]
    ),
    'json' => new Undercloud\Psr18\Streams\JsonStream([
        'foo' => 'bar
    ]),
    'photos' => [
        new Undercloud\Psr18\Streams\FileStream('/path/to/01.jpg'),
        new Undercloud\Psr18\Streams\FileStream('/path/to/02.jpg'),
        new Undercloud\Psr18\Streams\FileStream('/path/to/03.jpg'),
        ...
    ]
])
```

### FileStream

```php
new Undercloud\Psr18\Streams\FileStream($path, $filename = '')
```

### WrapStream
```php
// Third party stream

new Undercloud\Psr18\Streams\WrapStream(
    Psr\Http\Message\StreamInterface $stream
);
```

## Extra Multipart Headers

All kind of streams:
 - TextStream
 - JsonStream
 - SocketStream
 - MultipartStream
 - FileStream
 - WrapStream  
 
supports additional headers in multipart context
```php
new Undercloud\Psr18\Streams\MultipartStream([
    'photo' => new Undercloud\Psr18\Streams\FileStream($path)
        ->withHeader('Content-Transfer-Encoding', '8bit')   
])
```

## Options

 - **timeout** *(boolean)*  
  Number of seconds until the connect() system call should timeout.
  Defaults to 30.
  
 - **followLocation** *(boolean)*  
  Follow Location header redirects. Set to false to disable.
  Defaults to true. 
 
 - **maxRedirects** *(integer)*  
 The max number of redirects to follow. Value 1 or less means that no redirects are followed.
 Defaults to 5.

 - **ssl** *(array)*  
 SSL context options

### SSL Context Options

 - **peerName** *(string)*  
Peer name to be used. If this value is not set, then the name is guessed based on the hostname used when opening the stream.

 - **verifyPeer** *(boolean)*  
Require verification of SSL certificate used.  
Defaults to TRUE.

 - **verifyPeerName** *(boolean)*  
Require verification of peer name.  
Defaults to TRUE.

 - **allowSelfSigned** *(boolean)*  
Allow self-signed certificates. Requires **verifyPeer**.  
Defaults to FALSE

 - **cafile** *(string)*
Location of Certificate Authority file on local filesystem which should be used with the **verifyPeer** context option to authenticate the identity of the remote peer.

 - **capath** *(string)*  
If cafile is not specified or if the certificate is not found there, the directory pointed to by capath is searched for a suitable certificate. capath must be a correctly hashed certificate directory.

 - **localCert** *(string)*  
Path to local certificate file on filesystem. It must be a PEM encoded file which contains your certificate and private key. It can optionally contain the certificate chain of issuers. The private key also may be contained in a separate file specified by **localPk**.

 - **localPk** *(string)*  
Path to local private key file on filesystem in case of separate files for certificate (**localCert**) and private key.

 - **passphrase** *(string)*  
Passphrase with which your **localCert** file was encoded.

 - **CNMatch** *(string)*  
Common Name we are expecting. PHP will perform limited wildcard matching. If the Common Name does not match this, the connection attempt will fail.  
Note: This option is deprecated, in favour of **peerName**, as of PHP 5.6.0.

 - **verifyDepth** *(integer)*  
Abort if the certificate chain is too deep.  
Defaults to no verification.

 - **ciphers** *(string)*  
Sets the list of available ciphers. The format of the string is described in https://www.openssl.org/docs/manmaster/man1/ciphers.html#CIPHER-LIST-FORMAT. 
Defaults to DEFAULT.

 - **capturePeerCert** *(boolean)*  
If set to TRUE a peer_certificate context option will be created containing the peer certificate.

 - **capturePeerCertChain** *(boolean)*  
If set to TRUE a **peerCertificateChain** context option will be created containing the certificate chain.

 - **SNIEnabled** *(boolean)*
If set to TRUE server name indication will be enabled. Enabling SNI allows multiple certificates on the same IP address.

 - **SNIServerName** *(string)*  
If set, then this value will be used as server name for server name indication. If this value is not set, then the server name is guessed based on the hostname used when opening the stream.  
Note: This option is deprecated, in favour of **peerName**, as of PHP 5.6.0.

 - **disableCompression** *(boolean)*  
If set, disable TLS compression. This can help mitigate the CRIME attack vector.

 - **peerFingerprint** *(string | array)*  
Aborts when the remote certificate digest doesn't match the specified hash.
When a string is used, the length will determine which hashing algorithm is applied, either "md5" (32) or "sha1" (40).
When an array is used, the keys indicate the hashing algorithm name and each corresponding value is the expected digest.