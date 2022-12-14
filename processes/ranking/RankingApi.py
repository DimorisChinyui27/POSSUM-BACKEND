from trueskill import TrueSkill
import sys
import json

#Env is a TrueSkill environment variable that creates a ranking environment where the probability of a draw is 0
env= TrueSkill(draw_probability=0.0)

#Creates a rating object using env global variable
def Create_Rating(Nmu=25.0,Nsigma=8.333333333333334):
    return env.create_rating(mu=Nmu,sigma=Nsigma)

#Changes user ranking once that user gets a crown or losses a crown
def UpdateUserRating():
    All_Users_Ratings_Data = json.loads(sys.argv[1])
    Player_id = int(sys.argv[2])
    win_status = bool(sys.argv[3])
    Player_rating_obj=None
    Player_rating_replacement_obj=None
    Competitors_list=[]
    New_Ratings_Data=[]
    for statdict in All_Users_Ratings_Data:
        if Player_id==statdict['user_id']:
            #Creates rating objects for the user who got crown from that user ratings data in database
            Player_rating_obj=Create_Rating(Nmu=float(statdict['rating']),Nsigma=float(statdict['confidence_score']))
            Player_rating_replacement_obj=Player_rating_obj
        else:
            #Creates rating objects for other users from those users ratings data in database
            rating_obj= Create_Rating(Nmu=float(statdict['rating']),Nsigma=float(statdict['confidence_score']))
            Competitors_list.append({statdict['user_id']:rating_obj})

    #Updates the Players ranking and every other users ranking
    for i in range(len(Competitors_list)):
        for key in Competitors_list[i]:
            if win_status:
                if i==0:
                    #Updates the Players ranking and last user ranking
                    Player_rating_obj,Competitors_list[i][key]= env.rate_1vs1(Player_rating_obj,Competitors_list[i][key])
                else:
                    #Updates all other users ranking when compared to winner
                    _, Competitors_list[i][key]= env.rate_1vs1(Player_rating_replacement_obj,Competitors_list[i][key])
            else:
                if i==0:
                    #Updates the winners ranking and 1 other users ranking
                    Competitors_list[i][key],Player_rating_obj= env.rate_1vs1(Competitors_list[i][key],Player_rating_obj)
                else:
                     #Updates all other users ranking when compared to winner
                     Competitors_list[i][key],_= env.rate_1vs1(Competitors_list[i][key],Player_rating_replacement_obj)
            New_Ratings_Data.append({"user_id":key,"rating":Competitors_list[i][key].mu,"confidence_score":Competitors_list[i][key].sigma})

    New_Ratings_Data.append({"user_id":Player_id,"rating":Player_rating_obj.mu,"confidence_score":Player_rating_obj.sigma})
    print(json.dumps(New_Ratings_Data))


def Get_Experts_in_Topic(userTopics):

        Competitors_list=[]
        Search_top_expert_list=[]
        Top_Experts_List=[]
        top_expert=None
        All_Users_Ratings_Data = userTopics
        for statdict in All_Users_Ratings_Data:
            rating_obj= Create_Rating(Nmu=float(statdict['rating']),Nsigma=float(statdict['confidence_score']))
            Competitors_list.append({statdict['user_id']:rating_obj})
        #Searches for top ranking user
        for comp in Competitors_list:
            for key in comp:
                Search_top_expert_list.append(comp[key].mu)

        Search_top_expert_list.sort(reverse=True)

        for comp in Competitors_list:
            for key in comp:
                if comp[key].mu==Search_top_expert_list[0]:
                    top_expert=comp[key]
        #Compares top ranking user to others to get experts
        for comp in Competitors_list:
            for key in comp:
                #If probability of match quality==50% then other user also expert
                if env.quality_1vs1(top_expert,comp[key])>=0.50:
                    Top_Experts_List.append({key:comp[key].mu})
                else:
                    pass
        return Top_Experts_List

