# Разработка прототипов с помощью генерации кода

1. Запускаем контейнеры командой `docker-compose up -d`
2. Заходим в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняются из контейнера
3. Исправляем в `composer.json` секцию `autoload`
    ```json
    "psr-4": {
       "App\\": "src/",
       "FeedBundle\\": "src/FeedBundle",
       "Generated\\": "src/_GENERATED"
    }
    ```
4. Создаём каталог `src/_GENERATED` и выполняем в контейнере команду `composer dump-autoload`
5. В файле `config/services.yaml` добавляем новые сервисы
    ```yaml
    builder_factory:
        class: PhpParser\BuilderFactory
    
    App\Command\MakeApplicationCommand:
        tags: [ 'maker.command' ]
        arguments:
            - '@maker.file_manager'
            - '@builder_factory'
    ```
6. Добавляем шаблон класса `templates/DTO.tpl.php`
    ```php
    <?= "<?php\n"; ?>
    
    namespace <?= $namespace; ?>;
    
    use JMS\Serializer\Annotation as JMS;
    
    class <?= $className ?>
    
    {
    }
    ```
7. Добавляем класс `App\Service\AbstractClassGenerator`
    ```php
    <?php
    
    namespace App\Service;
    
    use Exception;
    use PhpParser\BuilderFactory;
    use Symfony\Bundle\MakerBundle\Generator;
    use Symfony\Bundle\MakerBundle\Str;
    use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
    
    abstract class AbstractClassGenerator
    {
        private const TEMPLATES_PATH = __DIR__.'/../../templates/';
    
        private string $templatePath;

        private Generator $generator;

        private BuilderFactory $factory;
    
        public function __construct(Generator $generator, BuilderFactory $factory, ?string $templatePath = null)
        {
            $this->generator = $generator;
            $this->templatePath = $templatePath ?? self::TEMPLATES_PATH;
            $this->factory = $factory;
        }
    
        protected function getGenerator(): Generator
        {
            return $this->generator;
        }
    
        protected function getBuilderFactory(): BuilderFactory
        {
            return $this->factory;
        }
    
        protected function createClassNameDetails(string $name, string $namespacePrefix, string $suffix = ''): ClassNameDetails
        {
            $className = rtrim($namespacePrefix, '\\').'\\'.Str::asClassName($name, $suffix);
    
            return new ClassNameDetails($className, $namespacePrefix, $suffix);
        }
    
        protected function getDTOClassDetails(string $entityName): ClassNameDetails
        {
            return $this->createClassNameDetails($entityName,'Generated\\DTO\\', 'DTO');
        }
    
        /**
         * @throws Exception
         */
        protected function generateClass(ClassNameDetails $classNameDetails, string $templateName, ?array $variables = null): string
        {
            $variables = array_merge($variables ?? [], [
                'className' => $classNameDetails->getShortName(),
            ]);
            $result = $this->generator->generateClass(
                $classNameDetails->getFullName(),
                $this->templatePath.$templateName,
                $variables
            );
            $this->generator->writeChanges();
    
            return $result;
        }
    
    }
    ```
8. Добавляем класс `App\Service\AbstractClassManipulatorAndGenerator`
    ```php
    <?php
    
    namespace App\Service;
    
    use PhpParser\BuilderFactory;
    use Symfony\Bundle\MakerBundle\ConsoleStyle;
    use Symfony\Bundle\MakerBundle\FileManager;
    use Symfony\Bundle\MakerBundle\Generator;
    use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
    
    abstract class AbstractClassManipulatorAndGenerator extends AbstractClassGenerator
    {
        private FileManager $fileManager;
    
        public function __construct(Generator $generator, BuilderFactory $factory, FileManager $fileManager, ?string $templatePath = null)
        {
            parent::__construct($generator, $factory, $templatePath);
            $this->fileManager = $fileManager;
        }
    
        public function dumpFile(string $filename, string $content): void
        {
            $this->fileManager->dumpFile($filename, $content);
        }
    
        protected function createClassManipulator(string $path, ConsoleStyle $io): ClassSourceManipulator
        {
            $manipulator = new ClassSourceManipulator($this->fileManager->getFileContents($path), false);
            $manipulator->setIo($io);
    
            return $manipulator;
        }
    }
    ```
9. Добавляем класс `App\Service\DTOClassGenerator`
    ```php
    <?php
    
    namespace App\Service;
    
    use Exception;
    use PhpParser\Node\Expr\Assign;
    use PhpParser\Node\Stmt\Expression;
    use Symfony\Bundle\MakerBundle\ConsoleStyle;
    use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
    
    class DTOClassGenerator extends AbstractClassManipulatorAndGenerator
    {
        public function generateDTOClass(string $name, array $properties, ConsoleStyle $io): void
        {
            $dtoClassDetails = $this->getDTOClassDetails($name);
            $dtoPath = $this->generateClass(
                $dtoClassDetails,
                'DTO.tpl.php'
            );
            $this->updateDTOClass($dtoPath, $properties, $io);
        }
    
        /**
         * @throws Exception
         */
        private function updateDTOClass(string $dtoPath, array $properties, ConsoleStyle $io): void
        {
            $dtoManipulator = $this->createClassManipulator($dtoPath, $io);
            $this->addConstructor($dtoManipulator, $properties);
            foreach ($properties as $fieldName => $type) {
                $this->addDTOField($dtoManipulator, $fieldName, ($type === 'string') ? 'string' : 'int');
            }
            $this->dumpFile($dtoPath, $dtoManipulator->getSourceCode());
        }
    
        private function addConstructor(ClassSourceManipulator $manipulator, array $fields): void
        {
            $factory = $this->getBuilderFactory();
            $methodBuilder = $factory->method('__construct')
                ->makePublic()
                ->addParam($factory->param('entity'));
            foreach ($fields as $fieldName => $type) {
                if ($type === 'field') {
                    $getterName = 'get'. ucfirst(substr($fieldName, 0, -2));
                    $getterCall = $factory->methodCall($factory->var('entity'), $getterName);
                    $idGetterCall = $factory->methodCall($getterCall, 'getId');
                    $fillFieldStatement = new Expression(new Assign(
                        $factory->propertyFetch($factory->var('this'), $fieldName),
                        $idGetterCall
                    ));
                    $methodBuilder->addStmt($fillFieldStatement);
                } else {
                    $getterCall = $factory->methodCall($factory->var('entity'), 'get'.ucfirst($fieldName));
                    $fillFieldStatement = new Expression(new Assign(
                        $factory->propertyFetch($factory->var('this'), $fieldName),
                        $getterCall
                    ));
                    $methodBuilder->addStmt($fillFieldStatement);
                }
            }
            $manipulator->addMethodBuilder($methodBuilder);
        }
    
        private function addDTOField(ClassSourceManipulator $manipulator, string $fieldName, string $typeHint, array $comments = []): void
        {
            $comments = array_merge(
                $comments,
                [
                    "@var $typeHint",
                    "@JMS\Type(\"$typeHint\")",
                ]
            );
            $manipulator->addProperty($fieldName, $comments);
    
            $manipulator->addGetter($fieldName, $typeHint, false);
        }
    }
    ```
10. Добавляем класс `App\Command\MakeApplicationCommand`
     ```php
     <?php
    
     namespace App\Command;
    
     use App\Service\DTOClassGenerator;
     use PhpParser\BuilderFactory;
     use Symfony\Bundle\MakerBundle\ConsoleStyle;
     use Symfony\Bundle\MakerBundle\DependencyBuilder;
     use Symfony\Bundle\MakerBundle\Doctrine\ORMDependencyBuilder;
     use Symfony\Bundle\MakerBundle\Generator;
     use Symfony\Bundle\MakerBundle\InputAwareMakerInterface;
     use Symfony\Bundle\MakerBundle\InputConfiguration;
     use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
     use Symfony\Bundle\MakerBundle\FileManager;
     use Symfony\Component\Console\Command\Command;
     use Symfony\Component\Console\Input\InputInterface;
    
     class MakeApplicationCommand extends AbstractMaker implements InputAwareMakerInterface
     {
         private FileManager $fileManager;
    
         private BuilderFactory $builderFactory;
    
         public function __construct(FileManager $fileManager, BuilderFactory $builderFactory)
         {
             $this->fileManager = $fileManager;
             $this->builderFactory = $builderFactory;
         }
    
         public static function getCommandName(): string
         {
             return 'make:application';
         }
    
         public static function getCommandDescription(): string
         {
             return 'Creates or updates a Doctrine entity class, and optionally an API Platform resource';
         }
    
         public function configureCommand(Command $command, InputConfiguration $inputConfig): void
         {
             $inputConfig->setArgumentAsNonInteractive('name');
         }
    
         public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
         {
             $dtos = [
                 'teacher' => ['name' => 'string', 'surname' => 'string', 'patronymic' => 'string'],
                 'student' => ['name' => 'string', 'surname' => 'string', 'patronymic' => 'string', 'teacherId' => 'field'],
             ];
    
             foreach ($dtos as $name => $properties) {
                 (new DTOClassGenerator($generator, $this->builderFactory, $this->fileManager))->generateDTOClass($name, $properties, $io);
             }
         }
    
         public function configureDependencies(DependencyBuilder $dependencies, InputInterface $input = null): void
         {
             ORMDependencyBuilder::buildDependencies($dependencies);
         }
     }
      ```
11. Выполняем команду `php bin/console make:application`, видим сгенерированные файлы.
