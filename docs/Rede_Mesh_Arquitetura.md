# Arquitetura da Rede Mesh LoRa - Documentação Técnica

## Visão Geral do Sistema

A Rede Mesh LoRa IBYT é uma solução de IoT (Internet das Coisas) desenvolvida para monitoramento remoto de níveis em reservatórios. A solução utiliza tecnologia LoRa para comunicação sem fio de longo alcance e baixo consumo, combinada com uma arquitetura mesh que permite a transmissão de dados mesmo em áreas com cobertura limitada.

![Visão Geral da Arquitetura](arquitetura_geral.png)

## Componentes do Sistema

O sistema é composto por três componentes principais:

1. **Medidores LoRa Mesh**: Dispositivos de campo que realizam medições e formam a rede mesh
2. **Gateway LoRa**: Ponte entre a rede LoRa e a internet
3. **Servidor IBYT**: Backend que recebe, processa e armazena os dados

## Fluxo de Dados

1. **Coleta**: Os medidores coletam dados de nível dos reservatórios
2. **Formatação**: Os dados são formatados como `C2R1N50` (Cliente, Reservatório, Nível)
3. **Transmissão Primária**: Tentativa de envio direto para o gateway
4. **Transmissão Mesh**: Se falhar, os dados são encaminhados para outro medidor
5. **Gateway**: Recebe os dados e encaminha para o servidor IBYT
6. **Servidor**: Processa e armazena os dados para visualização e análise

## Arquitetura Mesh

### Conceito

Uma rede mesh é uma topologia de rede onde cada nó (medidor) pode atuar como transmissor e receptor. Diferente de topologias tradicionais (estrela, árvore), a rede mesh permite múltiplos caminhos para os dados, aumentando a resiliência e a cobertura.

### Implementação

Na nossa implementação, cada medidor segue este algoritmo de decisão:

```
1. Medir nível
2. Tentar enviar para o gateway
3. Se falhar:
   a. Se tiver WiFi, enviar diretamente para a API
   b. Se não, encaminhar para outro medidor
4. Ao receber dados de outro medidor:
   a. Tentar enviar para o gateway
   b. Se falhar e tiver WiFi, enviar para API
   c. Se ambos falharem, encaminhar para outro medidor
```

### Vantagens da Arquitetura Mesh

1. **Resiliência**: Múltiplos caminhos para os dados
2. **Extensão de Cobertura**: Cada medidor amplia a área de cobertura
3. **Auto-organização**: A rede adapta-se automaticamente a falhas
4. **Escalabilidade**: Fácil adição de novos medidores
5. **Economia de Energia**: Comunicação LoRa de baixo consumo

## Segurança do Sistema

### Autenticação e Autorização

1. **Prefixo de Empresa**: Todas as mensagens válidas contêm o prefixo "IBYT:"
2. **Filtragem no Gateway**: Mensagens sem o prefixo são descartadas
3. **Lista de Dispositivos Autorizados**: Opção para verificar IDs específicos
4. **Interface Protegida**: Acesso à configuração protegido por senha

### Proteção contra Interferência

1. **Mensagens não autorizadas**: Descartadas pelo gateway
2. **Verificação de formato**: Validação da estrutura das mensagens
3. **Identificação de origem**: Opção para verificar a origem das mensagens

## Configuração e Manutenção

### Interface Web

Tanto os medidores quanto o gateway possuem interfaces web para configuração:

1. **Conexão WiFi**: SSID e senha
2. **Parâmetros de Identificação**: Cliente, reservatório
3. **Endpoint da API**: URL do servidor
4. **Segurança**: Senhas e dispositivos autorizados

### Modo AP

Em caso de falha na conexão WiFi, os dispositivos entram em modo AP (Access Point):

1. **Medidor**: Cria rede "Medidor_LoRa_Config"
2. **Gateway**: Cria rede "LoRa_Gateway_Config"
3. **Acesso**: Via IP 192.168.4.1
4. **Autenticação**: Senha padrão "#1@2#3$4%5"

## Formato da Comunicação

### Entre Medidores e Gateway

```
IBYT:C2R1N50
```

Onde:
- `IBYT:` = Prefixo de identificação da empresa
- `C2` = Código do cliente
- `R1` = Código do reservatório
- `N50` = Nível medido (50%)

### Entre Gateway e Servidor

```
https://ibyt.com.br/api.php/C2R1N50
```

## Minimização de Falhas

A arquitetura foi projetada para minimizar falhas através de:

1. **Redundância de Caminhos**: Múltiplas rotas para os dados
2. **Modos de Transmissão Alternativos**: LoRa ou WiFi direto
3. **Verificação de Recepção**: Confirmação de recebimento (implementação futura)
4. **Detecção de Dispositivos Não Autorizados**: Filtragem no gateway
5. **Armazenamento Temporário**: Buffer de mensagens em caso de falha (implementação futura)

## Escalabilidade e Extensões Futuras

A arquitetura permite as seguintes extensões futuras:

1. **Confirmação de Recebimento (ACK)**: Implementação de confirmações
2. **Criptografia**: Adição de criptografia nas mensagens
3. **Compressão de Dados**: Para mensagens maiores
4. **Roteamento Inteligente**: Algoritmos avançados de escolha de rota
5. **Painel de Monitoramento**: Interface de visualização de status da rede mesh

## Conclusão

A Rede Mesh LoRa IBYT representa uma solução inovadora para monitoramento remoto, combinando:

1. **Tecnologia LoRa**: Para comunicação de longo alcance e baixo consumo
2. **Arquitetura Mesh**: Para resiliência e extensão de cobertura
3. **Segurança**: Mecanismos de autenticação e autorização
4. **Simplicidade de Configuração**: Interfaces web intuitivas

Esta arquitetura permite a implantação de soluções de monitoramento em áreas remotas ou com infraestrutura limitada, garantindo a entrega confiável dos dados ao servidor IBYT.
