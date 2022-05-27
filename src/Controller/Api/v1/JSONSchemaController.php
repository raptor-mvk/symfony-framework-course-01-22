<?php

namespace App\Controller\Api\v1;

use ApiPlatform\Core\Hydra\JsonSchema\SchemaFactory;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;

#[Rest\Route(path: 'api/v1/json-schema')]
class JSONSchemaController extends AbstractFOSRestController
{
    private SchemaFactory $jsonSchemaFactory;

    public function __construct(SchemaFactory $jsonSchemaFactory)
    {
        $this->jsonSchemaFactory = $jsonSchemaFactory;
    }

    #[Rest\Get('')]
    #[QueryParam(name:'resource')]
    public function getJSONSchemaAction(string $resource): View
    {
        $className = 'App\\Entity\\'.ucfirst($resource);
        $schema = $this->jsonSchemaFactory->buildSchema($className);
        $arraySchema = json_decode(json_encode($schema), true);
        foreach ($arraySchema['definitions'] as $key => $value) {
            $entityKey = $key;
            break;
        }
        $unnecessaryPropertyKeys = array_filter(
            array_keys($arraySchema['definitions'][$entityKey]['properties']),
            static function (string $key) {
                return $key[0] === '@';
            }
        );
        foreach ($unnecessaryPropertyKeys as $key) {
            unset($arraySchema['definitions'][$entityKey]['properties'][$key]);
        }

        return View::create($arraySchema);
    }
}
