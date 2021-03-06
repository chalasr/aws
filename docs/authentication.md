# Authentication

To make requests to AWS, [credentials](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html) are required.

## Using the AWS Credentials File and Credential Profiles

The AWS CLI stores configuration and credential in [plain text files](https://docs.aws.amazon.com/cli/latest/userguide/cli-configure-files.html).

The format of the AWS credentials file should look something like the following.

```ini
; ~/.aws/credentials
[default]
aws_access_key_id = AKIAIOSFODNN7EXAMPLE
aws_secret_access_key = wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY

[project1]
aws_access_key_id = AKIAIOSFODNN7EXAMPLE
aws_secret_access_key = wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
```

Each section represents a credential `Profile` which can be referenced in the Configuration

```php
use AsyncAws\Core\AwsClientFactory;

$client = new AwsClientFactory([
    'profile' => 'project1'
]);
```

> **NOTE**: When not defined, AsyncAWS will use the profile named `default`.

The profile can refer to a [Role](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles.html) and specify the
source of another profile that contains the IAM user credentials with permission to use the role.

```ini
; ~/.aws/config:
[profile user1]
aws_access_key_id = AKIAIOSFODNN7EXAMPLE
aws_secret_access_key = wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY

[profile marketing]
role_arn = arn:aws:iam::123456789012:role/marketing
source_profile = user1
```

AWS also store Credentials in the `config` file that should like.

```ini
; ~/.aws/config:
[profile default]
aws_access_key_id = AKIAIOSFODNN7EXAMPLE
aws_secret_access_key = wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
region=eu-west-1

[profile project2]
aws_access_key_id = AKIAIOSFODNN7EXAMPLE
aws_secret_access_key = wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
region=us-west-2
```

The path to the `credentials` and `config` file can be *optionnaly* configured either with Configuration or env variables.

```cli
$ export AWS_SHARED_CREDENTIALS_FILE=/path/to/shared_credentials_file
$ export AWS_CONFIG_FILE=/path/to/config_file
```

```php
use AsyncAws\Core\AwsClientFactory;

$client = new AwsClientFactory([
    'sharedCredentialsFile' => '/path/to/shared_credentials_file',
    'sharedConfigFile' => '/path/to/config_file',
]);
```

## Using Credentials from Environment Variables

AsyncAWS recognize Env variables that are used by most official AWS tools and SDK.

```cli
$ export AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
   # The access key for your AWS account.
$ export AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
   # The secret access key for your AWS account.
$ export AWS_SESSION_TOKEN=AQoDYXdzEJr...<remainder of security token>
   # The session key for your AWS account. This is needed only when you are using temporary credentials.
```

> **NOTE**: You cannot mix env variables with configuration config with
> hard-coded configuration.

```php
use AsyncAws\Core\AwsClientFactory;

$client = new AwsClientFactory();
```

## Using Hard-Coded Configuration

When developing, and debugging, the simplest way to configure the client, is to set the credentials in the
client configuration parameters.

> **WARNING**: Hard-coding your credentials can be dangerous because it’s easy to commit your credentials into an SCM
> repository accidentally

```php
use AsyncAws\Core\AwsClientFactory;

$client = new AwsClientFactory([
    'accessKeyId' => 'AKIAIOSFODNN7EXAMPLE',
    'accessKeySecret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
]);
```

## Using EC2 Instance Metadata

When you run code within an EC2 instance (or EKS, Lambda), AsyncAws is able to fetch Credentials from the
[Role](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles.html) attached to the instance.

When running a single application on the Server, this is the simplest way to grant permissions to the application. You
have nothing to configure on the application, you only grant permissions on the Role attached to the instance.

## Using WebIdentity

When user have been authenticated in a mobile or web application with a web
identity provider (Amazon Cognito, Login with Amazon, Facebook, Google, or any
OpenID Connect-compatible identity provider). AsyncAws is able to fetch
[Credentials from a Role](https://docs.amazonaws.cn/en_us/IAM/latest/UserGuide/id_roles_create_for-idp_oidc.html)
assumed by the WebIdentity.

That authentication mechanism allows use of fined-grained Roles in an EKS
cluster (AWS managed service for K8S). It let you assign a specific role per
POD. Similar to [EC2 Instance Metadata](#using-ec2-instance-metadata) you
have nothing to configure on the application, you only configure the role and
permission assumed by the POD's ServiceAccount. See [AWS documentation](https://aws.amazon.com/blogs/opensource/introducing-fine-grained-iam-roles-service-accounts/)
for more information.

## Precedence of Providers

By default AsyncAWS uses a Provider that chain over all providers and uses the first provider in the chain that returns
credentials without an error.

The providers are currently chained in the following order:

- [Hard-Coded Configuration](#using-hard-coded-configuration)
- [Env Variables](#using-credentials-from-environment-variables)
- [WebIdentity](#using-webidentity)
- [Credential and Configuration Files](#using-the-aws-credentials-file-and-credential-profiles)
- [EC2 Instance Metadata](#using-ec2-instance-metadata)
