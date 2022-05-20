# API Platform: введение

## Устанавливаем API Platform

1. Забираем последний релиз API Platform из https://github.com/api-platform/api-platform/releases
2. Распаковываем архив
3. В файле `api/.env` добавляем переменную `SHELL_VERBOSITY=-1`
4. Запускаем контейнеры командой `docker-compose up -d`
5. Заходим по адресу https://localhost, соглашаемся на невалидный сертификат
6. Проверяем работоспособность документации API и панели администрирования

## Добавляем сущности

1. Добавляем класс `App\Entity\Person`
    ```php
    <?php
    
    namespace App\Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;
    
    #[ORM\MappedSuperclass]
    class Person
    {
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        public string $firstName;
    
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        public string $lastName;
    
        #[ORM\Column(type: 'integer', nullable: false)]
        #[Assert\NotBlank]
        #[Assert\Range(min: 0)]
        public int $age;
    }
    ```
2. Добавляем класс `App\Entity\Student`
    ```php
    <?php
    
    namespace App\Entity;
    
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\ORM\Mapping as ORM;
    
    #[ApiResource]
    #[ORM\Entity]
    class Student extends Person
    {
        #[ORM\Column(type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
    
        #[ORM\ManyToOne(targetEntity: 'Teacher', fetch: 'LAZY')]
        #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: 'true')]
        public ?Teacher $teacher;
    
        public function getId(): int
        {
            return (int)$this->id;
        }
    }
    ```
3. Добавляем класс `App\Entity\Teacher`
    ```php
    <?php
     
    namespace App\Entity;
     
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ORM\Mapping as ORM;
     
    #[ApiResource]
    #[ORM\Entity]
    class Teacher extends Person
    {
        #[ORM\Column(type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
     
        #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: 'Student', fetch: 'LAZY')]
        public Collection $students;
     
        public function __construct()
        {
            $this->students = new ArrayCollection();
        }
         
        public function getId(): int
        {
            return (int)$this->id;
        }
    }
    ```
4. Обновляем `docker-compose exec php bin/console doctrine:schema:update --force`
5. Заходим в панель администрирования и проверяем работоспособность новых сущностей

## Добавляем группы сериализации

1. Исправляем атрибуты в классе `App\Entity\Person`
    ```php
    <?php
     
    namespace App\Entity;
     
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Serializer\Annotation\Groups;
    use Symfony\Component\Validator\Constraints as Assert;
     
    #[ORM\MappedSuperclass]
    class Person
    {
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        #[Groups(['student:get', 'teacher:get'])]
        public string $firstName;
     
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        #[Groups(['student:get', 'teacher:get'])]
        public string $lastName;
     
        #[ORM\Column(type: 'integer', nullable: false)]
        #[Assert\NotBlank]
        #[Assert\Range(min: 0)]
        #[Groups(['student:get'])]
        public int $age;
    }
    ```
2. Исправляем атрибуты в классе `App\Entity\Student`
    ```php
    <?php
     
    namespace App\Entity;
     
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Serializer\Annotation\Groups;
     
    #[ApiResource(normalizationContext: ['groups' => ['student:get']])]
    #[ORM\Entity]
    class Student extends Person
    {
        #[ORM\Column(type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
     
        #[ORM\ManyToOne(targetEntity: 'Teacher', fetch: 'LAZY')]
        #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: 'true')]
        #[Groups(['student:get'])]
        public ?Teacher $teacher;
     
        public function getId(): int
        {
            return (int)$this->id;
        }
    }
    ```
3. Исправляем атрибуты в классе `App\Entity\Teacher`
    ```php
    <?php
     
    namespace App\Entity;
     
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Serializer\Annotation\Groups;
     
    #[ApiResource(normalizationContext: ['groups' => ['teacher:get']])]
    #[ORM\Entity]
    class Teacher extends Person
    {
        #[ORM\Column(type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
     
        #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: 'Student', fetch: 'LAZY')]
        #[Groups(['teacher:get'])]
        public Collection $students;
     
        public function __construct()
        {
            $this->students = new ArrayCollection();
        }
     
        public function getId(): int
        {
            return (int)$this->id;
        }
    }
    ```
4. Видим, что возраст теперь отображается только для студентов, но не отображаются ссылки.

## Исправляем отображение ссылок

1. Убираем атрибуты в классе `App\Entity\Person`
    ```php
    <?php
    
    namespace App\Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;
    
    #[ORM\MappedSuperclass]
    class Person
    {
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        public string $firstName;
    
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        public string $lastName;
    
        #[ORM\Column(type: 'integer', nullable: false)]
        #[Assert\NotBlank]
        #[Assert\Range(min: 0)]
        public int $age;
    }
    ```
2. Добавляем методы с атрибутами в классе `App\Entity\Student`
    ```php
    #[Groups(['student:get'])]
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    #[Groups(['student:get'])]
    public function getLastName(): string
    {
        return $this->lastName;
    }

    #[Groups(['student:get'])]
    public function getAge(): int
    {
        return $this->age;
    }
    ```
3. Добавляем методы с атрибутами в классе `App\Entity\Teacher`
    ```php
    #[Groups(['teacher:get'])]
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    #[Groups(['teacher:get'])]
    public function getLastName(): string
    {
        return $this->lastName;
    }
    ```
4. Видим, что ссылки исправились и возраст учителя не отображается.

## Отображаем имена вместо идентификаторов

1. В файле `App\Entity\Person` добавляем атрибут на поле `$firstName`
    ```php
    #[ORM\Column(type: 'string', length: 32, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    #[ApiProperty(iri:'http://schema.org/name')]
    public string $firstName;
    ```
2. Видим, что в ссылках теперь отображаются имена

## Заменяем связь на many-to-many

1. Исправляем класс `App\Entity\Student`
    ```php
    <?php
    
    namespace App\Entity;
    
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Serializer\Annotation\Groups;
    
    #[ApiResource(normalizationContext: ['groups' => ['student:get']])]
    #[ORM\Entity]
    class Student extends Person
    {
        #[ORM\Column(type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
    
        #[ORM\ManyToMany(targetEntity: 'Teacher', inversedBy: 'students', fetch: 'LAZY')]
        #[ORM\JoinTable(name: 'student_teacher')]
        #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id')]
        #[ORM\InverseJoinColumn(name: 'teacher_id', referencedColumnName: 'id')]
        #[Groups(['student:get'])]
        public Collection $teachers;
    
        public function getId(): int
        {
            return (int)$this->id;
        }
    
        #[Groups(['student:get'])]
        public function getFirstName(): string
        {
            return $this->firstName;
        }
    
        #[Groups(['student:get'])]
        public function getLastName(): string
        {
            return $this->lastName;
        }
    
        #[Groups(['student:get'])]
        public function getAge(): int
        {
            return $this->age;
        }
    
        public function __construct()
        {
            $this->teachers = new ArrayCollection();
        }
    
        /**
         * @return Teacher[]
         */
        public function getTeachers(): array
        {
            return $this->teachers->getValues();
        }
    
        public function addTeacher(Teacher $teacher): void
        {
            if ($this->teachers->contains($teacher)) {
                return;
            }
            $this->teachers->add($teacher);
            $teacher->addStudent($this);
        }
    
        public function removeTeacher(Teacher $teacher): void
        {
            if (!$this->teachers->contains($teacher)) {
                return;
            }
            $this->teachers->removeElement($teacher);
            $teacher->removeStudent($this);
        }
    }
    ```
2. Исправляем класс `App\Entity\Teacher`
    ```php
    <?php
    
    namespace App\Entity;
    
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Serializer\Annotation\Groups;
    
    #[ApiResource(normalizationContext: ['groups' => ['teacher:get']])]
    #[ORM\Entity]
    class Teacher extends Person
    {
        #[ORM\Column(type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
    
        #[ORM\ManyToMany(targetEntity: 'Student', inversedBy: 'teachers', fetch: 'LAZY')]
        #[Groups(['teacher:get'])]
        public Collection $students;
    
        public function __construct()
        {
            $this->students = new ArrayCollection();
        }
    
        public function getId(): int
        {
            return (int)$this->id;
        }
    
        #[Groups(['teacher:get'])]
        public function getFirstName(): string
        {
            return $this->firstName;
        }
    
        #[Groups(['teacher:get'])]
        public function getLastName(): string
        {
            return $this->lastName;
        }
    
        /**
         * @return Student[]
         */
        public function getStudents(): array
        {
            return $this->students->getValues();
        }
    
        public function addStudent(Student $student): void
        {
            if ($this->students->contains($student)) {
                return;
            }
            $this->students->add($student);
            $student->addTeacher($this);
        }
    
        public function removeStudent(Student $student): void
        {
            if (!$this->students->contains($student)) {
                return;
            }
            $this->students->removeElement($student);
            $student->removeTeacher($this);
        }
    }
    ```
3. Выполняем команду `docker-compose exec php bin/console doctrine:schema:update --force`

## Добавляем свой атрибут

1. Добавляем класс `App\Attributes\Extra`
    ```php
    <?php
    
    namespace App\Attributes;
    
    use Attribute;
    use Symfony\Contracts\Service\Attribute\Required;
    
    #[Attribute]
    class Extra
    {
        #[Required]
        public string $value;
    
        public int $number;
    
        public function __construct(string $value, int $number)
        {
            $this->value = $value;
            $this->number = $number;
        }
    }
    ```
2. В классе `App\Entity\Student`
    1. Добавляем атрибут к классу
        ```php
        #[ApiResource(normalizationContext: ['groups' => ['student:get']])]
        #[ORM\Entity]
        #[Extra(value: 'Student', number: 3)]
        ```
    2. Добавляем метод `getAttribute`
        ```php
        #[Groups(['student:get'])]
        public function getAttribute(): string
        {
            $reflectionClass = new \ReflectionClass(self::class);
            $extraAttributes = $reflectionClass->getAttributes(Extra::class);
     
            foreach ($extraAttributes as $attribute) {
                /** @var Extra $extra */
                $extra = $attribute->newInstance();
    
                return $extra->value.'$'.$extra->number;
            }
        }
        ```
3. Видим в списке студентов параметры нашего атрибута
