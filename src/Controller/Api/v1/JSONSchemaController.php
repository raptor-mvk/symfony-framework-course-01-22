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
        return View::create($arraySchema);
    }
}
