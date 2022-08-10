2022-08-10:
These files are for the JoomlaArt JA_FINDUS template. They will not standalone.

They extend the template to include:

1. Hours of Business
   1. Seprate hours for each day of the week, in the format HH:MM - HH:MM (Open - Closed, 24hr clock), keywork "Closed" can also be used. Any other text will be displayed as is.
   2. The business can be flagged as "Closed" on a temporary or permanant basis. This overrides any times enetered for each day.
   3. Using dates provided by JEvent component, public holidays can be defined and each business can be show as open/closed on these days (default is closed).
2. Food Menu Items (in Listing Fields)
   1. added Menu Type as a multi-choice, drop-down to define the food type (e.g. Vegan, Veggie, etc).
   2. added Menu Price for the price of the dish
   3. added icons to assist with Menu Type under media "images/icons".
3. JAMegafilter
   1. the default "position" sort ortder has been removed and replaced by "name", which is the name of the business.
   2. options for number of rows to display has been extended.

Caveat: You need to have installed JEvents component and defined a category of "Public Hoilidays". Each public holiday should be defined in this category as a single day for each one.

Still to do:

1. JA Megafilter is a complex and convoluted component. So far I haven't been able to work out how to include fields defined under "Extra Fields" in the output without going against the way that the filter works.
