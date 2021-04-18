(function()
{
	JSShop.Language.Translations =
	{
		Locale:
		{
			DecimalSeparator:	",",
			Currency:			"DKK",
			WeightUnit:			"Kg",
			DateFormat:			"DD-MM-YYYY",
			TimeFormat:			"hh:mm"
		},

		ProductForm:
		{
			EditProduct:		"Rediger produkt",
			NoProducts:			"Ingen produkter endnu",
			Category:			"Kategori",
			ProductId:			"Produkt ID",
			Title:				"Titel",
			Description:		"Beskrivelse",
			Images:				"Billeder",
			SelectFiles:		"Vælg fil(er)",
			Price:				"Pris excl. moms",
			Vat:				"Moms (%)",
			Weight:				"Vægt",
			Pounds:				"Pund (lbs.)",
			Kilos:				"Kilo (kg.)",
			DeliveryTime:		"Leveringstid",
			DiscountExpression:	"Rabatberegning",
			DiscountMessage:	"Rabatbesked",
			Save:				"Gem",
			Clear:				"Rens",
			Delete:				"Slet",
			DeleteWarning:		"Slet produkt?",
			ImagesNotRemoved:	"Et eller flere billeder kunne ikke slettes",
			NumericValueError:	"Værdi kan ikke overstige 9999999999,9999"
		},

		ProductList:
		{
			ProductAdded:		"Produkt tilføjet til indkøbskurven",
			OpenBasket:			"Gå til indkøbskurv",
			ContinueShopping:	"Køb mere",
			ErrorNotFound:		"Produkt blev ikke fundet"
		},

		Basket:
		{
			BasketEmpty:		"Indkøbskurven er tom",
			Product:			"Produkt",
			UnitPrice:			"Enhedspris",
			Units:				"Antal",
			Discount:			"Rabat",
			Price:				"Pris",
			TotalVat:			"Moms",
			TotalPrice:			"Pris inkl. moms",
			NumberOfUnits:		"Antal enheder",
			ErrorCurrencies:	"Fejl - det er ikke understøttet at blande produkter med forskellige valutaer",
			ErrorWeightUnits:	"Fejl - det er ikke understøttet at blande produkter med forskellige vægtenheder"
		},

		OrderForm:
		{
			CustomerDetails:	"Kundeinformation",
			Company:			"Firma",
			FirstName:			"Fornavn",
			LastName:			"Efternavn",
			Address:			"Addresse",
			ZipCode:			"Postnummer",
			City:				"By",
			Email:				"E-mail",
			Phone:				"Telefon",
			Message:			"Besked",
			RememberMe:			"Husk mig",
			AlternativeAddress:	"Alternativ leveringsaddresse",
			Payment:			"Betaling",
			Terms:				"Handelsbetingelser",
			PaymentAndTerms:	"Betaling og handelsbetingelser",
			PromotionCode:		"Rabatkode",
			PaymentMethod:		"Betalingsmetode",
			AcceptTerms:		"Jeg accepterer handelsbetingelserne",
			TermsRequired:		"Accepterer venligst handelsbetingelserne for at fortsætte",
			Read:				"Læs",
			Continue:			"Fortsæt",
			OrderReceived:		"Din ordre er modtaget - tak",
			MissingPhoneNumber:	"Indtast venligst dit telefonnummer hvis du har et",
			ContinueWithout:	"Fortsæt uden",
			AddPhoneNumber:		"Tilføj telefonnummer",
			EnterValidEmail:	"Indtast en gyldig e-mail adresse",
			EnterValidZipCode:	"Indtast et gyldigt postnummer (tal)",
			PartialAltAddress:	"Du har kun delvist udfyldt felterne under Alternativ Leveringsadresse - udfyld venligst resten, eller slå Alternativ Leveringsadresse fra",
			PleaseWait:			"Vi arbejder stadig på det - hav venligst tålmodighed lidt endnu",
			BasketUpdated:		"Indkøbskurven blev opdateret"
		},

		OrderList:
		{
			Search:				"Søg",
			DisplayFromDate:	"Vis ordre fra denne dato",
			DisplayToDate:		"Vis ordre til denne dato",
			Update:				"Opdater",
			Export:				"Eksport",
			ChooseFormat:		"Vælg venligst format",
			SendInvoice:		"Send faktura",
			Capture:			"Hæv",
			Reject:				"Afvis",
			Settings:			"Indstillinger",
			OrderId:			"Ordrenr.",
			Time:				"Tidspunkt",
			Customer:			"Kunde",
			Amount:				"Beløb",
			PaymentMethod:		"Type",
			State:				"Tilstand",
			InvoiceId:			"Fakturanr.",
			StateInitial:		"Initiel",
			StateAuthorized:	"Godkendt",
			StateCaptured:		"Hævet",
			StateCanceled:		"Afvist",
			SelectOrders:		"Vælg venligst en eller flere ordre",
			ConfirmAction:		"Bekræft venligst handlingen",
			Processing:			"Vent venlist mens data behandles..",
			NavigateAway:		"Du er ved at annullere behandling af data!",
			DoneSuccess:		"Færdig - alle ordre behandlet",
			DoneFailure:		"Færdig - følgende ordre fejlede",
			Order:				"Bestilling",
			SearchErrorField:	"Søgeudtrykket refererer et ukendt felt",
			SearchErrorOperator:"Søgeudtrykket benytter en ugyldig operator",
			IgnoresUncaptured:	"Følgende ordre ignoreres da de ikke er blevet hævet først",

			// Dialog: Customer details
			CustomerDetails:	"Kundedetaljer",
			AlternativeAddress:	"Alternativ leveringsadresse",
			Message:			"Besked",
			CustomData:			"Brugerdefineret data",

			// Dialog: Order Entries
			Product:			"Produkt",
			UnitPrice:			"Enhedspris",
			Units:				"Antal",
			Price:				"Pris",
			TotalVat:			"Moms",
			TotalPrice:			"Pris inkl. moms",

			// Dialog: Alternative state (tags)
			PaymentState:		"Betalingstilstand",
			CustomState:		"Brugerdefineret tilstand (tags)",
			RenameTag:			"Omdøb tagget '{0}' til:",
			UnknownTag:			"UKENDT",
			DeleteTagWarning:	"Er du fuldstændig sikker på at du vil slette tagget '{0}'? Det kan være i brug på andre ordre hvor det fremadrettet vil blive vist som UKENDT."
		},

		Config:
		{
			Basic:				"Primær",
			EmailTemplates:		"E-mail skabeloner",
			PaymentMethods:		"Betalingsmetoder",
			Advanced:			"Advanceret",
			Save:				"Gem",
			Done:				"Færdig",
			Receipt:			"Kvittering",
			Terms:				"Vilkår",
			BccEmail:			"BCC e-mail addresse der modtager kopier af alle e-mails sendt",
			Subject:			"Emne",
			Content:			"Indhold",
			AutoLineBreaks:		"Konverter automatisk linjeskift til <br> HTML linjeskift (standard)",
			EnableModule:		"Aktiver dette betalingsmodul",
			Title:				"Titel",
			Enabled:			"Aktiveret",
			CostCorrection:		"Prisregulering",
			AdditionalData:		"Yderligere data",
			ConfirmTemplate:	"Bekræftelses e-mail skabelon",
			InvoiceTemplate:	"Faktura e-mail skabelon",
			CostExpression:		"Pris-udtryk",
			VatExpression:		"Moms-udtryk",
			MessageExpression:	"Besked-udtryk",
			AdditionalDataJson:	"Yderligere data (JSON objekt)",
			Identifiers:		"Identifikatorer",
			NextOrderId:		"Næste ordrenummer",
			NextInvoiceId:		"Næste fakturanummer",
			InvalidJson:		"Ugyldigt JSON objekt",
			InvalidExpression:	"Ugyldigt udtryk",
			Behaviour:			"Adfærd",
			CaptureFirst:		"En ordre skal hæves før udsendelse af en faktura"
		},

		Common:
		{
			Ok:					"OK",
			Cancel:				"Annuller",
			Required:			"Feltet skal udfyldes",
			MaxLengthExceeded:	"Værdien indtastet overstiger det maksimalt tilladte antal karakterer",
			InvalidValue:		"Ugyldig værdi",
			InvalidEntries:		"Et eller flere felter er ikke udfyldt korrekt"
		}
	};
})();
