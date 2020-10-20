<?php

namespace App\Services;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use Exception;
use GuzzleHttp\Client;
use Respect\Validation\Validator as v;

class Contest
{
    public $data;
    public $status = true;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->generateCsv();
    }

    public function validatePostData()
    {
        $validAccount = v::ArrayVal()
            ->key('email', v::email()->notEmpty())
            ->key('name', v::StringType()->notEmpty()->length(3, 50))
            ->key('surname', v::StringType()->notEmpty()->length(3, 100))
            ->key('instagram', v::StringType()->notEmpty()->length(3, 100))
            ->key('instagram2', v::StringType()->notEmpty()->length(3, 100))
            ->key('file', v::notEmpty()->image()->size('10KB', '5MB'))
            ->key('mobile', v::phone()->notEmpty()->length(9, 9))
            ->key('city', v::StringType()->notEmpty()->length(3, 100))
            ->key('gender', v::StringType()->notEmpty()->in(['Male', 'Female']))
            ->key('birthdate', v::date('Y-m-d')->notEmpty())
            ->key('legal', v::intVal()->notEmpty()->equals(1))
            ->key('privacy', v::intVal()->notEmpty()->equals(1));

        $validAccount->assert($this->data);

        return $this;
    }

    public function sendToDatacentric()
    {
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://registropg.com/es/urfwsRest/api/',
            // You can set any number of default request options.
            'timeout' => 2.0,
        ]);

        $response = $client->request('GET', 'basicdata', [
            'query' => [
                'accountId' => $GLOBALS['config']['datacentric']['accountId'],
                'incentiveId' => $GLOBALS['config']['datacentric']['incentiveId'],
                'email' => $this->data['email'],
                'name' => $this->data['name'],
                'surname' => $this->data['surname'],
                'mobile' => $this->data['mobile'],
                'city' => $this->data['city'],
                'gender' => $this->data['gender'],
                'birthdate' => $this->data['birthdate'],
            ]
        ]);

        $body = $response->getBody()->getContents();
        $body = (string)substr($body, 1, -1);
        $body = str_replace('\r\n', '', $body);
        $body = stripslashes($body);

        $sxe = simplexml_load_string($body);

        if ($sxe->Result == 'ERROR') {
            $this->status = false;

            foreach ($sxe->Errors as $error) {
                $error = $GLOBALS['config']['datacentric']['errors'][(string)$error->Error] ?? 'Ha habido un error al enviar tu concurso, inténtalo de nuevo mas tarde.';

                throw new Exception($error, 500);
            }
        }

        return $this;
    }

    public function sendToAzure()
    {
        $container = 'files';
        $connectionString = 'DefaultEndpointsProtocol=https;AccountName=' . $GLOBALS['env']['azure']['blob']['AccountName'] . ';AccountKey=' . $GLOBALS['env']['azure']['blob']['AccountKey'];

        try {
            $content = fopen($GLOBALS['config']['csv'], 'r');

            $blobClient = BlobRestProxy::createBlobService($connectionString);

            $blobClient->createBlockBlob($container, $GLOBALS['config']['web_slug'] . '/contest/contest.csv', $content);

            $this->uploadImage($blobClient, $container);
        } catch (ServiceException $e) {
            die(var_dump($e));
            throw new Exception($e, 500);
        }

        return $this;
    }

    private function uploadImage(BlobRestProxy $blobClient, $container)
    {
        try {
            $imageName = $this->setImageName();

            $content = fopen($this->data['file'], 'r');

            $blobClient->createBlockBlob($container, $GLOBALS['config']['web_slug'] . '/contest/' . $imageName, $content);
        } catch (ServiceException $e) {
            throw new Exception($e, 500);
        }
    }

    private function setImageName()
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // devuelve el tipo mime de su extensión

        $mime = finfo_file($finfo, $this->data['file']);

        if (!array_key_exists($mime, $GLOBALS['config']['mime'])) {
            throw new Exception('Tipo de imagen no válida', 500);
        }

        finfo_close($finfo);

        return $this->data['email'] . '.' . $GLOBALS['config']['mime'][$mime];
    }

    private function generateCsv($delimiter = ',', $enclosure = '"')
    {
        $handle = fopen($GLOBALS['config']['csv'], 'a');

        $data = $this->data;
        unset($data['file']);

        fputcsv($handle, $data, $delimiter, $enclosure);
    }
}
