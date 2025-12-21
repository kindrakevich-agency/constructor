# OpenAI SEO Text Generator

This feature automatically generates SEO-optimized descriptions for taxonomy terms using OpenAI's GPT models.

## How It Works

1. **Select a taxonomy vocabulary** - Choose which vocabulary's terms you want to process
2. **Optional: Specify a term ID** - Process a single term, or leave blank to process all terms
3. **Click "Create SEO texts for terms"** - The system will:
   - Find each term in the selected vocabulary
   - Look for the latest published node that has that term attached
   - Extract the body text from that node (up to 2000 characters)
   - Send the term name and body text to OpenAI
   - Generate an SEO-friendly description (150-200 characters)
   - Save the generated text to the term's description field

## Requirements

This feature requires the OpenAI PHP client library:

```bash
composer require openai-php/client:^0.17
```

This dependency is declared in the module's `composer.json` file and should be installed automatically when you install the module via Composer.

## Setup

### 1. Get an OpenAI API Key

1. Visit [OpenAI Platform](https://platform.openai.com/api-keys)
2. Sign in or create an account
3. Generate a new API key
4. Copy the key (you won't be able to see it again)

### 2. Configure the Module

1. Navigate to: `/admin/config/search/simple-metatag/openai`
2. Enter your OpenAI API Key
3. Select your preferred model:
   - **GPT-4o Mini** (Recommended) - Fast and cost-effective
   - **GPT-4o** - Highest quality
   - **GPT-4 Turbo** - Good balance
   - **GPT-3.5 Turbo** - Legacy option
4. Click "Save Settings"

## Usage

### Generate for All Terms in a Vocabulary

1. Select a vocabulary from the dropdown
2. Leave the "Term ID" field blank
3. Click "Create SEO texts for terms"
4. The batch process will show progress
5. Review the results summary

### Generate for a Single Term

1. Select a vocabulary from the dropdown
2. Enter a specific term ID
3. Click "Create SEO texts for terms"
4. The term will be processed immediately

## What Gets Skipped

Terms are automatically skipped if:
- No published nodes are attached to the term
- The attached node has no body text
- The term ID is invalid or doesn't belong to the selected vocabulary

## Batch Processing

When processing multiple terms:
- Each term is processed individually
- Progress is shown in real-time
- Results summary shows:
  - **Success**: Terms with generated descriptions
  - **Skipped**: Terms without content
  - **Failed**: Terms that encountered errors

## Cost Considerations

- OpenAI charges per token (roughly per 4 characters)
- GPT-4o Mini is the most cost-effective option (~$0.15 per 1M input tokens)
- Each term processes approximately:
  - System prompt: ~50 tokens
  - Term name + body excerpt: ~500-600 tokens
  - Response: ~40-50 tokens
- Estimated cost per term: ~$0.0001 (GPT-4o Mini)

## Best Practices

1. **Test with a single term first** - Verify the output quality before batch processing
2. **Review generated content** - AI-generated content should be reviewed for accuracy
3. **Keep API key secure** - Never commit it to version control
4. **Monitor API usage** - Check your OpenAI dashboard for usage and costs
5. **Choose the right model**:
   - Use GPT-4o Mini for most cases (fast and cheap)
   - Use GPT-4o if you need higher quality output

## Troubleshooting

### "OpenAI API error" in logs

Check the Drupal logs: `/admin/reports/dblog`

Common issues:
- Invalid API key
- Insufficient API credits
- Rate limit exceeded
- Network connectivity issues

### Terms Being Skipped

Reasons a term might be skipped:
- No nodes have that term assigned
- All nodes with that term are unpublished
- The node's body field is empty

### No Response Generated

If the batch completes but no descriptions are generated:
1. Check your OpenAI API key is correct
2. Verify you have sufficient API credits
3. Ensure the OpenAI PHP client library is installed: `composer require openai-php/client:^0.17`
4. Check Drupal logs for detailed error messages (look for "OpenAI PHP client library is not installed")
5. Test with the OpenAI Playground to verify your API key works

## Security Notes

- API keys are stored in Drupal configuration
- Only users with "administer simple metatag" permission can access
- API keys are not exposed in the UI after initial entry
- Consider using environment variables for production deployments

## Example Workflow

```
1. Navigate to: /admin/config/search/simple-metatag/openai
2. Enter API Key: sk-proj-xxxxx...
3. Select Model: GPT-4o Mini
4. Save Settings
5. Select Vocabulary: Tags
6. Leave Term ID blank (to process all)
7. Click "Create SEO texts for terms"
8. Wait for batch to complete
9. Review results: Success: 45, Skipped: 5, Failed: 0
10. Check term descriptions at: /admin/structure/taxonomy/manage/tags/overview
```

## Technical Details

### API Prompt Structure

The system uses this prompt structure:

```
System: You are an SEO expert who writes compelling, concise meta descriptions.

User: Based on the following article content and topic name, generate a
concise SEO-friendly description (150-200 characters) that would work
well as a meta description.

Topic: [Term Name]

Article excerpt:
[First 2000 characters of node body]

Generate only the SEO description text, without any additional
commentary or quotes.
```

### Temperature: 0.7
- Balanced between creativity and consistency
- Produces natural, varied descriptions

### Max Tokens: 150
- Ensures concise responses
- Keeps costs low
