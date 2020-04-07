<template>
    <div v-if="isValid" class="flex-no-shrink ml-2 float-right mb-4">
        <button class="btn btn-default btn-primary pb-8"
                data-testid="run-resource-index-button"
                dusk="resource-index-button"
                v-html="buttonText"
                @click.prevent="determineActionStrategy"
        >
        </button>

        <!-- Action Confirmation Modal -->
        <portal to="modals" transition="fade-transition">
            <component
                    :is="selectedAction.component"
                    :working="working"
                    v-if="showActionModal"
                    :selected-resources="selectedResources"
                    :resource-name="resourceName"
                    :action="selectedAction"
                    :errors="errors"
                    @confirm="executeAction"
                    @close="closeConfirmationModal"
            />
        </portal>
        <!-- </portal> -->
    </div>
</template>

<script>
    import { InteractsWithResourceInformation } from 'laravel-nova'
    import RenderButton from '@/mixins/RenderButton';
    import HandlesAction from '@/mixins/HandlesAction';
    export default {

        mixins: [ InteractsWithResourceInformation, RenderButton, HandlesAction ],

        props: {
            card: {
                type: Object
            }
        },

        data: () => ({
            isAction: false,
            showActionModal: false,
        }),

        async created() {
            if(this.card.action){
                this.getAction();
            }
        },

        /**
         * Mount the component and retrieve its initial data.
         */
        mounted() {
            this.$nextTick(this.renderButton);
        },

        watch: {
            /**
             * Watch the actions property for changes.
             */
            selectedAction() {
                if(!this.selectedAction)
                    this.initializeActionFields()
            }
        },

        computed: {
            /*
             * Checks if the button should be displayed
             */
            isValid() {
                return this.isAction || this.card.isLink || this.card.isRoute;
            },
            /*
             * Checks if it is an action
             */
            isAnAction() {
                return this.isAction;
            },
            /*
             * Checks if it is a link
             */
            isALink() {
                return this.card.isLink;
            },
            /*
             * Checks if it is a route
             */
            isARoute() {
                return this.card.isRoute;
            },
            /*
             * Used to get the text for the button
             */
            buttonText(){
                return this.card.buttonText
            }
        }
    }

</script>
