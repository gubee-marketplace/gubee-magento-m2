# Magento Gubee Integration 

![GitHub repo size](https://img.shields.io/github/repo-size/maco-studios/gubee-integration?style=for-the-badge)
![GitHub language count](https://img.shields.io/github/languages/count/maco-studios/gubee-integration?style=for-the-badge)
![GitHub forks](https://img.shields.io/github/forks/maco-studios/gubee-integration?style=for-the-badge)


## 💻 Pré-requisitos

Antes de começar, verifique se você atendeu aos seguintes requisitos:

- Você instalou a versão  do `php 7.4` ou superior.
- Você instalou a versão  do `composer 1` ou superior.
- Você instalou a versão  do `Magento 2.4.1` ou superior.

## 🚀 Instalando modulo no Magento 2

Para instalar o [Gubee Integration](https://github.com/maco-studios/gubee-integration), siga estas etapas:

Dentro do projeto Magento 2:

```shell
composer require gubee/integration:^1
```
## 🚀 Desinstalando modulo no Magento 2
Rodar comando: DELETE FROM `patch_list`WHERE ((`patch_name` = 'Gubee\Integration\Setup\Patch\Data\GubeeCatalogProductAttribute'));
bin/magento module:uninstall Gubee_Integration


## ☕ Usando Gubee Integration

### _Em breve._

## 📝 Licença

Esse projeto está sob licença. Veja o arquivo [LICENÇA](LICENSE.md) para mais detalhes.
