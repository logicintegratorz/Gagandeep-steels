# Products and pricing

Dashboard > Rates opens product pricing, separate from Freight rates. Company selection and date determine the displayed rate. Retail is used when present; otherwise purchase is used. Rates accept free text. Use Edit / update rate to save a company and dated rate. Changes appear in Rate log sheet grouped by company, with company/date filters and previous values. Inactive products are hidden from pricing. Images are private and require authorized access.

## Product sizes

Click the product name in Rates to expand its details directly below the pricing section on the same page. Selecting another product replaces the visible details; changing the selected company refreshes its base rate and calculated size price. The header displays product name and price, size search in the middle, and HSN code on the right. Below it, select a saved size to fill Basic difference, CC, Size price and Weight. Notes stays manual and is not saved from this view. Search filters the size dropdown by prefix. All sizes belong to their product and are shared across its company rates.

Add sizes directly below the expanded product details on Rates, or at Products > Edit product > Add size. The Rates form returns to the same product and company, selects the new size, and shows it in Saved sizes beneath the form. Enter size, Basic difference, Weight, CC and Notes accept ordinary text. The saved list appears on the edit page. Numeric base rate and difference are added exactly; text values display Not calculable. View permission is required to view sizes; view and edit permissions are required to add them.

The local startup script applies migrations automatically. Previously overwritten rates from before audit logging cannot be reconstructed.

Each size has an optional free-text Base price stored separately. The calculated Size price uses the selected product/company price plus that size's Basic difference, both in the main field and related-size rows. Changing company updates this calculation. The saved size Base price is displayed separately and does not enter this calculation.

## Company price popup
Open a company dropdown to see price, company and last update date/time (IST). Edit company prices opens a two-column editor. Plus adds an empty row; minus removes a row when saved. At least one company row remains. New rows are company rates for the selected product, not separate products. Save updates today's prices, records changes/removals in the rate log, and refreshes downstream size calculations. Historical pricing remains accessible. Edit permission is required; stale editors are rejected to avoid overwriting another user's changes.
