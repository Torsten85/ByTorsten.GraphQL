# GraphQL - the flow way
this graphql package was inspired by [Wwwision.GraphQL](https://github.com/bwaidelich/Wwwision.GraphQL). But it just felt like a wrapper, I'd like a package that seamlessly integrates in Flow. 
So here it is.
 
## Installation
For know you have to manually add this repository to your composer.json. 
This pacakge is not jet published on packagist.org

## Integration
### Add an endpoint to your `Settings.yaml`

```
ByTorsten:
  GraphQL:
    endpoints:
      graphql: 'Vendor.Package'
```

### Add a graphql schema
Simply place a file in `Resources/Private/GraphQL`. The filename doesn't metter as long as it has the `.graphql` extension.
Multiple files will be merged into one single schema. This file uses the [grpahql schema language](http://graphql.org/learn/schema/).

```
type Query {
    myVehicle: Vehicle
}

interface Vehicle {
  maxSpeed: Int
}

type Airplane implements Vehicle {
  maxSpeed: Int
  wingspan: Int
}

type Car implements Vehicle {
  maxSpeed: Int
  licensePlate: String
}
```

### Add resolvers
Resolvers behave quite like action controllers. The have to be placed in `Classes/Resolver` and extend `ByTorsten\GraphQL\Resolver\ResolverController`.
The Controller name reflects the graphql type, the action (here resolver) reflects the property.
 
For the above example, you should create 2 resolverControllers:

`QueryResolverController.php`:
```
<?php
namespace Vendor\Package\Resolver;

use ByTorsten\GraphQL\Resolver\ResolverController;

class QueryResolverController extends ResolverController
{

    /**
     * @return array
     */
    public function myVehicleResolver()
    {
        return [
            'maxSpeed' => 10,
            'wingspan' => 20
        ];
    }
}
```

`VehicleResolverController.php`:
```
<?php
namespace Vendor\package\Resolver;

use ByTorsten\GraphQL\Resolver\ResolverController;

class VehicleResolverController extends ResolverController
{


    /**
     * Because Vehicle is defined as interface in the schema, you have to implement reoslveType
     * @param $obj
     * @param $context
     * @param ResolveInfo $info
     * @return string
     */
    public function resolveType($obj, $context, ResolveInfo $info): string
    {
        if (isset($obj['wingspan'])) {
            return 'Airplane';
        }

        return 'Car';
    }
}
```

And thanks it.
A more complete exmaple will follow.