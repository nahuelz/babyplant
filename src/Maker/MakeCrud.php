<?php

namespace App\Maker;

use Symfony\Bundle\MakerBundle\Str;
use Doctrine\Inflector\InflectorFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Validator\Validation;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Console\Question\Question;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Doctrine\Common\Inflector\Inflector as LegacyInflector;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

final class MakeCrud extends AbstractMaker
{
    private $doctrineHelper;
    private $formTypeRenderer;
    private $inflector;
    private $sourceTemplatesPath;
    private $em;
    private $kernel_project_dir;

    public function __construct(DoctrineHelper $doctrineHelper, FormTypeRenderer $formTypeRenderer, string $kernel_project_dir, EntityManagerInterface $em)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->formTypeRenderer = $formTypeRenderer;
        $this->sourceTemplatesPath = $kernel_project_dir . '/templates/skeleton/crud/';
        $this->em = $em;
        $this->kernel_project_dir = $kernel_project_dir;

        if (class_exists(InflectorFactory::class)) {
            $this->inflector = InflectorFactory::create()->build();
        }
    }

    public static function getCommandName(): string
    {
        return 'make:custom:crud';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates CRUD for Doctrine entity class')
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create CRUD (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            //->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeCrud.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($entities);

            $value = $io->askQuestion($question);

            $input->setArgument('entity-class', $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists($input->getArgument('entity-class'), $this->doctrineHelper->getEntitiesForAutocomplete()),
            'Entity\\'
        );

        // ASSOCIATION MAPPINGS
        $em = $this->em;
        $entityMetadata = $em->getMetadataFactory()->getMetadataFor('App\Entity\\' . $entityClassDetails->getRelativeName());
        $associationMappings = $entityMetadata->getAssociationMappings();
        $entityAssociationMappings = [];
        foreach($associationMappings as $associationMapping) {
            //TODO VER SI SE PUEDE AGREGAR MANY_TO_MANY Y ONE_TO_ONE
            if (ClassMetadataInfo::MANY_TO_ONE === $associationMapping['type']){
                //TODO REQUIRED
                $associationMapping['required'] = false;
                $entityAssociationMappings[] = $associationMapping;
            }
        }

        $entityDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($entityClassDetails->getFullName());

        $repositoryVars = [];

        if (null !== $entityDoctrineDetails->getRepositoryClass()) {
            $repositoryClassDetails = $generator->createClassNameDetails(
                '\\'.$entityDoctrineDetails->getRepositoryClass(),
                'Repository\\',
                'Repository'
            );

            $repositoryVars = [
                'repository_full_class_name' => $repositoryClassDetails->getFullName(),
                'repository_class_name' => $repositoryClassDetails->getShortName(),
                'repository_var' => lcfirst($this->singularize($repositoryClassDetails->getShortName())),
            ];
        }

        $controllerClassDetails = $generator->createClassNameDetails(
            $entityClassDetails->getRelativeNameWithoutSuffix().'Controller',
            'Controller\\',
            'Controller'
        );

        $iter = 0;
        do {
            $formClassDetails = $generator->createClassNameDetails(
                $entityClassDetails->getRelativeNameWithoutSuffix().($iter ?: '').'Type',
                'Form\\',
                'Type'
            );
            ++$iter;
        } while (class_exists($formClassDetails->getFullName()));

        $entityVarPlural = lcfirst($this->pluralize($entityClassDetails->getShortName()));
        $entityVarSingular = lcfirst($this->singularize($entityClassDetails->getShortName()));

        $entityTwigVarPlural = Str::asTwigVariable($entityVarPlural);
        $entityTwigVarSingular = Str::asTwigVariable($entityVarSingular);

        $routeName = Str::asRouteName($controllerClassDetails->getRelativeNameWithoutSuffix());
        //$templatesPath = Str::asFilePath($controllerClassDetails->getRelativeNameWithoutSuffix());

        $templatesPath = strtolower(str_replace('\\', '/', $controllerClassDetails->getRelativeNameWithoutSuffix()));

        $generator->generateController(
            $controllerClassDetails->getFullName(),
            $this->sourceTemplatesPath . 'controller/Controller.tpl.php',
            array_merge([
                    'entity_full_class_name' => $entityClassDetails->getFullName(),
                    'entity_class_name' => $entityClassDetails->getShortName(),
                    'form_full_class_name' => $formClassDetails->getFullName(),
                    'form_class_name' => $formClassDetails->getShortName(),
                    'route_path' => Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix()),
                    'route_name' => $routeName,
                    'templates_path' => $templatesPath,
                    'entity_var_plural' => $entityVarPlural,
                    'entity_twig_var_plural' => $entityTwigVarPlural,
                    'entity_var_singular' => $entityVarSingular,
                    'entity_twig_var_singular' => $entityTwigVarSingular,
                    'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                    'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                ],
                $repositoryVars
            )
        );
        
        /** TYPE * */
        $this->renderType(
                $formClassDetails,
                $entityDoctrineDetails->getFormFields(),
                $entityClassDetails,
                [],
                [],
                $entityDoctrineDetails->getDisplayFields(),
                $entityAssociationMappings,
                $generator
        );
                
        $jsPath = (Str::getNamespace($controllerClassDetails->getRelativeName()) == '' ? '' : strtolower(Str::getNamespace($controllerClassDetails->getRelativeName())) . '/') . strtolower($entityClassDetails->getShortName());

        $templates = [
            'index' => [
                'entity_twig_var_plural' => $entityTwigVarPlural,
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => strtolower($entityClassDetails->getShortName()),
                'js_path' => $jsPath
            ],
            'index_table' => [
                'entity_twig_var_plural' => $entityTwigVarPlural,
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => strtolower($entityClassDetails->getShortName()),
                'entity_twig_var_singular' => $entityTwigVarSingular,
            ],
            'new' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => strtolower($entityClassDetails->getShortName()),
                'association_fields' => $entityAssociationMappings,
                'js_path' => $jsPath
            ],
            'show' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => strtolower($entityClassDetails->getShortName()),
                'association_fields' => $entityAssociationMappings,
            ],
        ];

        foreach ($templates as $template => $variables) {
            $generator->generateTemplate(
                $templatesPath . '/' . $template . '.html.twig',
                $this->sourceTemplatesPath . 'templates/' . $template . '.tpl.php',
                $variables
            );            
        }

        /** JAVASCRIPT * */
        $indexJsName = 'public/js/app/' . $jsPath . '/index.js';
        $generator->generateFile(
            $this->kernel_project_dir . '/' . $indexJsName,
            $this->sourceTemplatesPath . 'js/index.tpl.php',
            [
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'entity_class_name' => $entityClassDetails->getShortName(),             
                'route_name' => strtolower($entityClassDetails->getShortName())
            ]
        );

        //$newJsName = 'public/js/app/' . strtolower($entityClassDetails->getShortName()) . '/new.js';
        $newJsName = 'public/js/app/' . $jsPath . '/new.js';
        $generator->generateFile(
            $this->kernel_project_dir . '/' . $newJsName,
            $this->sourceTemplatesPath . 'js/new.tpl.php',
            [
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_twig_var_singular' => $entityTwigVarSingular,   
                'route_name' => strtolower($entityClassDetails->getShortName())
            ]
        );        

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text(sprintf('Next: Check your new CRUD by going to <fg=yellow>%s/</>', Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix())));
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Route::class,
            'router'
        );

        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );

        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig-bundle'
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm-pack'
        );

        $dependencies->addClassDependency(
            CsrfTokenManager::class,
            'security-csrf'
        );

        $dependencies->addClassDependency(
            ParamConverter::class,
            'annotations'
        );
    }

    private function pluralize(string $word): string
    {
        if (null !== $this->inflector) {
            return $this->inflector->pluralize($word);
        }

        return LegacyInflector::pluralize($word);
    }

    private function singularize(string $word): string
    {
        if (null !== $this->inflector) {
            return $this->inflector->singularize($word);
        }

        return LegacyInflector::singularize($word);
    }
    
    /**
     * 
     * @param ClassNameDetails $formClassDetails
     * @param array $formFields
     * @param ClassNameDetails $boundClassDetails
     * @param array $constraintClasses
     * @param array $extraUseClasses
     * @param array $entity_fields
     * @param array $entity_association_mappings
     */
    public function renderType(ClassNameDetails $formClassDetails, array $formFields, $generator, ClassNameDetails $boundClassDetails = null, array $constraintClasses = [], array $extraUseClasses = [], array $entity_fields = [], array $entity_association_mappings = []) {
        $fieldTypeUseStatements = [];
        $fields = [];
        foreach ($formFields as $name => $fieldTypeOptions) {
            $fieldTypeOptions = $fieldTypeOptions ?? ['type' => null, 'options_code' => null];

            if (isset($fieldTypeOptions['type'])) {
                $fieldTypeUseStatements[] = $fieldTypeOptions['type'];
                $fieldTypeOptions['type'] = Str::getShortClassName($fieldTypeOptions['type']);
            }

            $fields[$name] = $fieldTypeOptions;
        }

        $mergedTypeUseStatements = array_unique(array_merge($fieldTypeUseStatements, $extraUseClasses));
        sort($mergedTypeUseStatements);

        $generator->generateClass(
                $formClassDetails->getFullName(),
                $this->sourceTemplatesPath . 'form/Type.tpl.php',
                [
                    'bounded_full_class_name' => $boundClassDetails ? $boundClassDetails->getFullName() : null,
                    'bounded_class_name' => $boundClassDetails ? $boundClassDetails->getShortName() : null,
                    'form_fields' => $fields,
                    'field_type_use_statements' => $mergedTypeUseStatements,
                    'constraint_use_statements' => $constraintClasses,
                    'fields' => $entity_fields,
                    'association_fields' => $entity_association_mappings
                ]
        );
    }
}