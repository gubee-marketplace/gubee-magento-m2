# Magento Gubee Integration 

![GitHub repo size](https://img.shields.io/github/repo-size/gubee-marketplace/gubee-magento-m2?style=for-the-badge)
![GitHub language count](https://img.shields.io/github/languages/count/gubee-marketplace/gubee-magento-m2?style=for-the-badge)
![GitHub forks](https://img.shields.io/github/forks/gubee-marketplace/gubee-magento-m2?style=for-the-badge)


## 💻 Pré-requisitos

Antes de começar, verifique se você atendeu aos seguintes requisitos:

- Você instalou a versão  do `php 7.4` ou superior.
- Você instalou a versão  do `composer 1` ou superior.
- Você instalou a versão  do `Magento 2.4.1` ou superior.

## 🚀 Instalando modulo no Magento 2

Para instalar o [Gubee Integration](https://github.com/maco-studios/gubee-integration), siga estas etapas:

Dentro do projeto Magento 2:

```shell
composer require gubee-marketplace/integration-module:^1
```
## 🚀 Desinstalando modulo no Magento 2
Rodar comando: DELETE FROM `patch_list`WHERE ((`patch_name` = 'Gubee\Integration\Setup\Patch\Data\GubeeCatalogProductAttribute'));
bin/magento module:uninstall Gubee_Integration


## ☕ Usando Gubee Integration

### Conectar com OAuth2

1. No painel da Gubee, acesse **Integrações → Meus apps** e registre um app. Habilite as permissões de leitura e escrita necessárias para as entidades que a loja sincroniza (catálogo, estoque, preço, pedidos, notas e expedição). Mantenha o app ativo.
2. No Magento, acesse **Lojas → Configuração → Gubee**, no escopo **Configuração padrão**. A conexão é única para toda a instalação, incluindo todos os websites e lojas.
3. Copie a URL de callback exibida em **Gubee Connection** e cadastre-a no app da Gubee, exatamente como exibida, incluindo a barra final. Use HTTPS em produção e cadastre uma URL para cada ambiente. A URL contém o caminho administrativo da instalação; atualize o cadastro se esse caminho ou domínio mudar.
4. Salve **OAuth Client ID** e **OAuth Client Secret**. Em seguida, clique em **Connect / Reconnect with Gubee** e autorize o app usando a conta operacional correta da Gubee. O segredo fica no servidor, criptografado no banco do Magento.
5. Ao retornar ao Magento, confira o status **Connected with OAuth**. Faça uma sincronização de teste das entidades habilitadas para confirmar as permissões do app.

O administrador precisa da permissão **Configure Gubee and OAuth Connection** e deve permanecer autenticado no mesmo navegador durante a autorização. Cada tentativa expira em dez minutos. Ao implantar a atualização, execute os comandos usuais de upgrade, compilação de DI e publicação de conteúdo estático do Magento; a conexão usa um novo arquivo JavaScript administrativo. Conceda a nova permissão aos perfis administrativos restritos que gerenciam a integração.

### Migração e renovação

Instalações existentes continuam usando a API Key até a primeira conexão OAuth bem-sucedida. Cancelar ou falhar na primeira autorização mantém a autenticação anterior. Novas instalações devem usar OAuth, sem preencher a chave legada.

Depois da conexão, o módulo renova o token automaticamente antes do vencimento e salva o refresh token rotacionado. Os tokens são criptografados e atualizados sob um lock compartilhado; em instalações com vários nós, configure o lock do Magento com um backend compartilhado, como banco de dados. Não use locks locais por arquivo nesse cenário.

Após migrar, o módulo não volta automaticamente à API Key. Se a Gubee rejeitar a renovação, confira se o app está ativo e se as credenciais estão corretas, depois use **Connect / Reconnect with Gubee**. Para autorizar novamente um app já conectado, pode ser necessário revogar a autorização anterior em **Integrações → Apps conectados** na Gubee. Falhas temporárias preservam os tokens e aguardam trinta segundos antes de outra tentativa. Operações afetadas falham sem enviar requisições sem autenticação; a fila mantém seu tratamento normal de falhas.

Ao rotacionar o segredo no painel da Gubee, salve o novo **OAuth Client Secret** no Magento imediatamente; ele será usado na próxima renovação. Alterar o Client ID exige uma nova conexão. Uma tentativa de reconexão com falha preserva os tokens anteriormente salvos.

Os segredos e tokens são classificados como configuração sensível e não entram no `app/etc/config.php` compartilhado. O estado da conexão é mantido no banco da instalação: não copie a conexão de produção para outros ambientes. Cabeçalhos de autenticação e parâmetros OAuth são ocultados nos logs do módulo. Configure também o proxy e o servidor web para não registrar os parâmetros da URL de callback.

Referência do protocolo: [Guia OAuth2 da Gubee](https://developers.gubee.com.br/oauth2/guia-do-desenvolvedor/).

## 📝 Licença

Esse projeto está sob licença. Veja o arquivo [LICENÇA](LICENSE.md) para mais detalhes.
