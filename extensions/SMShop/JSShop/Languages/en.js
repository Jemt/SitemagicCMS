(function()
{
	JSShop.Language.Translations =
	{
		Locale:
		{
			DecimalSeparator:	".",
			Currency:			"USD",
			WeightUnit:			"Lbs",
			DateFormat:			"DD-MM-YYYY",
			TimeFormat:			"hh:mm"
		},

		ProductForm:
		{
			EditProduct:		"Edit product",
			NoProducts:			"No products yet",
			Category:			"Category",
			ProductId:			"Product ID",
			Title:				"Title",
			Description:		"Description",
			Images:				"Images",
			SelectFiles:		"Select file(s)",
			Price:				"Price ex. VAT",
			Vat:				"VAT (%)",
			Weight:				"Weight",
			Pounds:				"Pounds (lbs.)",
			Kilos:				"Kilos (kg.)",
			DeliveryTime:		"Delivery time",
			DiscountExpression:	"Discount expression",
			DiscountMessage:	"Discount message",
			Save:				"Save",
			Clear:				"Clear",
			Delete:				"Delete",
			DeleteWarning:		"Delete product?",
			ImagesNotRemoved:	"One or more images could not be removed",
			NumericValueError:	"Value can not exceed 9999999999.9999"
		},

		ProductList:
		{
			ProductAdded:		"Product added to basket",
			OpenBasket:			"Go to basket",
			ContinueShopping:	"Continue shopping",
			ErrorNotFound:		"Product not found"
		},

		Basket:
		{
			BasketEmpty:		"Shopping basket is empty",
			Product:			"Product",
			UnitPrice:			"Unit price",
			Units:				"Units",
			Discount:			"Discount",
			Price:				"Price",
			TotalVat:			"VAT",
			TotalPrice:			"Price incl. VAT",
			NumberOfUnits:		"Number of units",
			ErrorCurrencies:	"Error - mixing products with different currencies is not supported",
			ErrorWeightUnits:	"Error - mixing products with different units of weight is not supported"
		},

		OrderForm:
		{
			CustomerDetails:	"Customer information",
			Company:			"Company",
			FirstName:			"First name",
			LastName:			"Last name",
			Address:			"Address",
			ZipCode:			"Zip code",
			City:				"City",
			Email:				"E-mail",
			Phone:				"Phone",
			Message:			"Message",
			RememberMe:			"Remember me",
			AlternativeAddress:	"Alternative delivery address",
			Payment:			"Payment",
			Terms:				"Terms",
			PaymentAndTerms:	"Payment and terms",
			PromotionCode:		"Promotion code",
			PaymentMethod:		"Payment method",
			AcceptTerms:		"I accept the terms and conditions",
			TermsRequired:		"Please accept terms and conditions to continue",
			Read:				"Read",
			Continue:			"Continue",
			OrderReceived:		"Your order has been received - thank you",
			MissingPhoneNumber:	"Please enter your phone number if you have one",
			ContinueWithout:	"Continue without",
			AddPhoneNumber:		"Add phone number",
			EnterValidEmail:	"Enter a valid e-mail address",
			EnterValidZipCode:	"Enter a valid zipcode (number)",
			PartialAltAddress:	"You have only partially filled out the fields under Alternative Delivery Address - please fill out the remaining fields, or turn off Alternative Delivery Address",
			PleaseWait:			"We are still working on it - please be patient a little longer",
			BasketUpdated:		"Basket was updated"
		},

		OrderList:
		{
			Search:				"Search",
			DisplayFromDate:	"Display orders from this date",
			DisplayToDate:		"Display orders to this date",
			Update:				"Update",
			Export:				"Export",
			ChooseFormat:		"Please choose format",
			SendInvoice:		"Send invoice",
			Capture:			"Withdraw",
			Reject:				"Reject",
			Settings:			"Settings",
			OrderId:			"Order no.",
			Time:				"Time",
			Customer:			"Customer",
			Amount:				"Amount",
			PaymentMethod:		"Type",
			State:				"State",
			InvoiceId:			"Invoice no.",
			StateInitial:		"Initial",
			StateAuthorized:	"Authorized",
			StateCaptured:		"Withdrawn",
			StateCanceled:		"Rejected",
			SelectOrders:		"Please select one or multiple orders",
			ConfirmAction:		"Please confirm action",
			Processing:			"Please wait while processing data..",
			NavigateAway:		"You are about to cancel data processing!",
			DoneSuccess:		"Done - all orders processed",
			DoneFailure:		"Done - the following orders failed",
			Order:				"Order",
			SearchErrorField:	"Search expression refer to an unknown field",
			SearchErrorOperator:"Search expression uses an invalid operator",

			// Dialog: Customer details
			CustomerDetails:	"Customer details",
			AlternativeAddress:	"Alternative delivery address",
			Message:			"Message",
			CustomData:			"Custom data",

			// Dialog: Order Entries
			Product:			"Product",
			UnitPrice:			"Unit price",
			Units:				"Units",
			Price:				"Price",
			TotalVat:			"VAT",
			TotalPrice:			"Price incl. VAT",

			// Dialog: Alternative state (tags)
			PaymentState:		"Payment state",
			CustomState:		"Custom state (tags)",
			RenameTag:			"Rename the tag '{0}' to:",
			UnknownTag:			"UNKNOWN",
			DeleteTagWarning:	"Are you absolutely sure you want to remove the tag '{0}'? It may be used on other orders where the tag going forward will be shown as UNKNOWN."
		},

		Config:
		{
			Basic:				"Basic",
			EmailTemplates:		"E-mail templates",
			PaymentMethods:		"Payment methods",
			Advanced:			"Advanced",
			Save:				"Save",
			Done:				"Done",
			Receipt:			"Receipt",
			Terms:				"Terms",
			BccEmail:			"BCC e-mail address receiving copies of all e-mails sent",
			Subject:			"Subject",
			Content:			"Content",
			AutoLineBreaks:		"Automatically turn line breaks into <br> HTML line breaks (default)",
			EnableModule:		"Enable this payment module",
			Title:				"Title",
			Enabled:			"Enabled",
			CostCorrection:		"Cost correction",
			AdditionalData:		"Additional data",
			ConfirmTemplate:	"Confirmation e-mail template",
			InvoiceTemplate:	"Invoice e-mail template",
			CostExpression:		"Cost expression",
			VatExpression:		"VAT expression",
			MessageExpression:	"Message expression",
			AdditionalDataJson:	"Additional data (JSON object)",
			Identifiers:		"Identifiers",
			NextOrderId:		"Next order number",
			NextInvoiceId:		"Next invoice number",
			InvalidJson:		"Invalid JSON object",
			InvalidExpression:	"Invalid expression"
		},

		Common:
		{
			Ok:					"OK",
			Cancel:				"Cancel",
			Required:			"Field must be filled out",
			MaxLengthExceeded:	"Value entered exceeds the maximum number of characters allowed",
			InvalidValue:		"Invalid value",
			InvalidEntries:		"One or more fields have not been filled out correctly"
		}
	};
})();
