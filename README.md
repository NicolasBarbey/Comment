# Module Comment

The module **Comment** allows customer to add comments on different elements of the website : product, content, ...

A comment is composed of a :

- title
- message
- rating
- is related to a customer

The message can be moderated by a administrator before being displayed on the website (recommended).

Only registered and logged in customer can post comment on the website. You can also only authorized customers to post
comment on products that they have bought. Customers will receive an email after 15 days (by default) to encourage them
to post comment.   

If the comment has been accepted the customer can edit or delete it.

This module is compatible with Thelia version 2.1 or greater.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is Comment.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/comment-module:~1.0
```

## Usage

In back-office, the configuration page allows you to configure the module.

In the tools menu, a new page displays comments and let you manage them.

In the front office, an integration is provided for the default template. It uses hooks, so it's activated by default.

You can override these smarty templates in the current template. You have to put your templates files in this directory
 (with default template) : `template/frontOffice/default/modules/Comment/`

## Loop

The module provides a new loop : **comment**

### Input arguments

|Argument |Description |
|---      |--- |
|**id**        | the comment id                                                     |
|**customer**  | the customer id                                                    |
|**ref**       | the reference key. eg : product                                    |
|**ref_id**    | the reference id. (the product id)                                 |
|**status**    | the status of the comment : 0 = pending, 1 = accepted              |
|**verified**  | the customer has bought the product                                |
|**locale**    | the locale of the comment : fr_FR                                  |
|**load_ref**  | load or not the reference object. default = 0                      |
|**ref_locale**| locale of the reference object fields. default: the request locale |

### Output arguments

|Variable   |Description |
|---        |--- |
|$ID          | the comment id                                                             |                                                                       
|$USERNAME    | the username                                                               |                                                                           
|$EMAIL       | the email                                                                  |                                                                    
|$CUSTOMER_ID | the customer id                                                            |                                                                                
|$REF         | the reference key                                                          |                                                                          
|$REF_ID      | the reference id                                                           |                                                                            
|$TITLE       | the title                                                                  |                                                                    
|$CONTENT     | the content                                                                |                                                                         
|$RATING      | the rating                                                                 |                                                                      
|$STATUS      | the status :  : 0 = pending, 1 = accepted                                  |                                                                                                      
|$VERIFIED    | 0 : not verified / not applicable, 1 = the customer has bought the product |                                                                                                                                        
|$ABUSE       | an abuse counter.                                                          |                                                                             

## how to get the rating of a product

Ratings are stored in the meta_data table. to retrieve the rating, you can use the smarty function `meta` like this :

```smarty
{$rating={meta meta="COMMENT_RATING" key="product" id="10"}}
{if $rating}
<span class="pull-right">
    rating: {$rating}
</span>
{/if}
```
